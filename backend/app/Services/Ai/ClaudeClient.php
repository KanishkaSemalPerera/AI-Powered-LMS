<?php

namespace App\Services\Ai;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\APITimeoutException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\BadRequestException;
use Anthropic\Core\Exceptions\InternalServerException;
use Anthropic\Core\Exceptions\NotFoundException;
use Anthropic\Core\Exceptions\PermissionDeniedException;
use Anthropic\Core\Exceptions\RateLimitException;
use App\Contracts\AiClient;
use App\Exceptions\AiException;
use App\Support\Ai\AiCompletion;
use App\Support\Ai\AiPrompt;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Log;
use Psr\Http\Client\ClientInterface;

/**
 * Claude via the official Anthropic PHP SDK (Messages API).
 *
 * - Model comes from ANTHROPIC_MODEL, so it can change without code changes.
 * - The timeout is enforced by the Guzzle transport (the SDK's own timeout
 *   option is advisory); the SDK retries 408/409/429/5xx itself.
 * - Requests opt into server-side refusal fallback, so a policy decline on
 *   the main model is retried on a substitute model before giving up.
 */
class ClaudeClient implements AiClient
{
    private const FALLBACK_BETA = 'server-side-fallback-2026-07-01';

    private ?Client $client = null;

    /** @param  ClientInterface|null  $transporter  PSR-18 client; tests inject a mock, production builds Guzzle */
    public function __construct(private readonly ?ClientInterface $transporter = null) {}

    public function isConfigured(): bool
    {
        return filled(config('services.anthropic.api_key'));
    }

    public function model(): string
    {
        return (string) config('services.anthropic.model');
    }

    public function complete(AiPrompt $prompt): AiCompletion
    {
        if (! $this->isConfigured()) {
            throw AiException::notConfigured();
        }

        $outputConfig = ['effort' => $prompt->effort];
        if ($prompt->jsonSchema !== null) {
            // Structured outputs: the reply is constrained to valid JSON matching the schema.
            $outputConfig['format'] = ['type' => 'json_schema', 'schema' => $prompt->jsonSchema];
        }

        $fallback = (bool) config('services.anthropic.refusal_fallback');

        try {
            $message = $this->client()->beta->messages->create(
                maxTokens: $prompt->maxTokens,
                messages: $prompt->messages,
                model: $this->model(),
                system: $prompt->system,
                outputConfig: $outputConfig,
                fallbacks: $fallback ? 'default' : null,
                betas: $fallback ? [self::FALLBACK_BETA] : null,
            );
        } catch (AuthenticationException|PermissionDeniedException|NotFoundException $e) {
            // Bad key, no access to the model, or unknown model id: an operator problem.
            Log::error('Claude API configuration error', ['status' => $e->getCode(), 'error' => $e->getMessage()]);
            throw new AiException('AI features are misconfigured on this server. Please contact support.', 503, 'misconfigured', $e);
        } catch (RateLimitException $e) {
            Log::warning('Claude API rate limited', ['error' => $e->getMessage()]);
            throw AiException::busy($e);
        } catch (BadRequestException $e) {
            Log::error('Claude API rejected the request', ['error' => $e->getMessage()]);
            throw new AiException('The AI request could not be processed. Please shorten your input and try again.', 422, 'bad_request', $e);
        } catch (InternalServerException $e) {
            Log::warning('Claude API server error', ['status' => $e->status, 'error' => $e->getMessage()]);
            // 529 = overloaded: temporary capacity, not an outage.
            throw $e->status === 529 ? AiException::busy($e) : AiException::unavailable($e);
        } catch (APIStatusException $e) {
            // Includes 529 overloaded and any other status.
            Log::warning('Claude API error', ['type' => $e->type?->value, 'error' => $e->getMessage()]);
            throw AiException::busy($e);
        } catch (APITimeoutException $e) {
            throw AiException::timeout($e);
        } catch (APIConnectionException $e) {
            // Guzzle transport timeouts arrive here, wrapped by the SDK.
            Log::warning('Claude API connection problem', ['error' => $e->getMessage()]);
            throw $this->isTimeout($e) ? AiException::timeout($e) : AiException::unavailable($e);
        }

        if ($message->stopReason === 'refusal') {
            throw AiException::refused();
        }
        if ($message->stopReason === 'max_tokens') {
            throw AiException::invalidOutput('the answer was cut off');
        }

        $text = '';
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        return new AiCompletion(
            text: trim($text),
            model: $message->model,
            inputTokens: $message->usage->inputTokens,
            outputTokens: $message->usage->outputTokens,
            stopReason: $message->stopReason,
        );
    }

    /** Looks through the wrapped exception chain for a transport timeout (cURL error 28). */
    private function isTimeout(\Throwable $e): bool
    {
        for ($current = $e; $current !== null; $current = $current->getPrevious()) {
            $message = strtolower($current->getMessage());
            if (str_contains($message, 'timed out') || str_contains($message, 'curl error 28')) {
                return true;
            }
        }

        return false;
    }

    private function client(): Client
    {
        return $this->client ??= new Client(
            apiKey: (string) config('services.anthropic.api_key'),
            requestOptions: [
                'maxRetries' => (int) config('services.anthropic.max_retries'),
                'timeout' => (float) config('services.anthropic.timeout'),
                'transporter' => $this->transporter ?? new GuzzleClient([
                    'timeout' => (float) config('services.anthropic.timeout'),
                    'connect_timeout' => 10,
                ]),
            ],
        );
    }
}
