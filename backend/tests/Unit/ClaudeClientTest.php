<?php

namespace Tests\Unit;

use App\Exceptions\AiException;
use App\Services\Ai\ClaudeClient;
use App\Support\Ai\AiPrompt;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\RequestInterface;
use Tests\TestCase;

/**
 * Exercises the real Anthropic SDK against canned HTTP responses (no network),
 * checking the request we send and how responses/errors are mapped.
 */
class ClaudeClientTest extends TestCase
{
    /** @var array<int, array{request: RequestInterface}> */
    private array $history = [];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.anthropic.api_key' => 'sk-ant-test',
            'services.anthropic.model' => 'claude-opus-5-5',
            'services.anthropic.max_retries' => 0,
            'services.anthropic.refusal_fallback' => true,
        ]);
    }

    private function client(array $responses): ClaudeClient
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        return new ClaudeClient(new GuzzleClient(['handler' => $stack]));
    }

    private function message(array $overrides = []): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode(array_merge([
            'id' => 'msg_test',
            'type' => 'message',
            'role' => 'assistant',
            'model' => 'claude-opus-5-5',
            'content' => [['type' => 'text', 'text' => '{"answer": 42}']],
            'stop_reason' => 'end_turn',
            'stop_sequence' => null,
            'usage' => ['input_tokens' => 321, 'output_tokens' => 45],
        ], $overrides)));
    }

    private function error(int $status, string $type): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode([
            'type' => 'error',
            'error' => ['type' => $type, 'message' => 'Simulated'],
        ]));
    }

    private function prompt(?array $schema = null): AiPrompt
    {
        return new AiPrompt('System text', [['role' => 'user', 'content' => 'Question?']], 4000, 'low', $schema);
    }

    public function test_successful_request_sends_the_expected_body_and_maps_the_reply(): void
    {
        $schema = ['type' => 'object', 'properties' => ['answer' => ['type' => 'integer']], 'required' => ['answer'], 'additionalProperties' => false];

        $completion = $this->client([$this->message()])->complete($this->prompt($schema));

        $this->assertSame('{"answer": 42}', $completion->text);
        $this->assertSame([321, 45], [$completion->inputTokens, $completion->outputTokens]);

        $request = $this->history[0]['request'];
        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('https://api.anthropic.com/v1/messages?beta=true', (string) $request->getUri());
        $this->assertSame('sk-ant-test', $request->getHeaderLine('x-api-key'));
        $this->assertStringContainsString('server-side-fallback-2026-07-01', $request->getHeaderLine('anthropic-beta'));
        $this->assertSame('claude-opus-5-5', $body['model']);
        $this->assertSame(4000, $body['max_tokens']);
        $this->assertSame('System text', $body['system']);
        $this->assertSame('default', $body['fallbacks']);
        $this->assertSame('low', $body['output_config']['effort']);
        $this->assertSame('json_schema', $body['output_config']['format']['type']);
        $this->assertSame($schema, $body['output_config']['format']['schema']);
    }

    public function test_model_is_read_from_configuration(): void
    {
        config(['services.anthropic.model' => 'claude-sonnet-5-5']);
        $this->client([$this->message()])->complete($this->prompt());

        $this->assertSame('claude-sonnet-5-5', json_decode((string) $this->history[0]['request']->getBody(), true)['model']);
    }

    public function test_refusal_stop_reason_becomes_a_refused_error(): void
    {
        $this->expectExceptionObject(AiException::refused());
        $this->client([$this->message(['stop_reason' => 'refusal', 'content' => []])])->complete($this->prompt());
    }

    public function test_truncated_output_is_reported_as_invalid(): void
    {
        try {
            $this->client([$this->message(['stop_reason' => 'max_tokens'])])->complete($this->prompt());
            $this->fail('Expected AiException');
        } catch (AiException $e) {
            $this->assertSame('invalid_output', $e->reason);
        }
    }

    #[DataProvider('errorProvider')]
    public function test_api_errors_map_to_friendly_exceptions(int $status, string $type, string $reason, int $httpStatus): void
    {
        try {
            $this->client([$this->error($status, $type)])->complete($this->prompt());
            $this->fail('Expected AiException');
        } catch (AiException $e) {
            $this->assertSame($reason, $e->reason);
            $this->assertSame($httpStatus, $e->status);
        }
    }

    public static function errorProvider(): array
    {
        return [
            'bad key' => [401, 'authentication_error', 'misconfigured', 503],
            'unknown model' => [404, 'not_found_error', 'misconfigured', 503],
            'rate limited' => [429, 'rate_limit_error', 'busy', 503],
            'bad request' => [400, 'invalid_request_error', 'bad_request', 422],
            'server error' => [500, 'api_error', 'unavailable', 503],
            'overloaded' => [529, 'overloaded_error', 'busy', 503],
        ];
    }

    public function test_network_timeouts_become_timeout_errors(): void
    {
        $timeout = new ConnectException('cURL error 28: Operation timed out after 120001 milliseconds', new Request('POST', 'https://api.anthropic.com/v1/messages'));

        try {
            $this->client([$timeout])->complete($this->prompt());
            $this->fail('Expected AiException');
        } catch (AiException $e) {
            $this->assertSame('timeout', $e->reason);
            $this->assertSame(504, $e->status);
        }
    }

    public function test_missing_key_never_calls_the_api(): void
    {
        config(['services.anthropic.api_key' => null]);

        try {
            $this->client([])->complete($this->prompt());
            $this->fail('Expected AiException');
        } catch (AiException $e) {
            $this->assertSame('not_configured', $e->reason);
            $this->assertSame([], $this->history);
        }
    }
}
