<?php

namespace Database\Factories;

use App\Enums\AiFeature;
use App\Enums\AiRequestStatus;
use App\Models\AiRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiRequest>
 */
class AiRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'feature' => fake()->randomElement(AiFeature::cases()),
            'model' => config('services.anthropic.model'),
            'input_tokens' => fake()->numberBetween(200, 3000),
            'output_tokens' => fake()->numberBetween(50, 1500),
            'duration_ms' => fake()->numberBetween(800, 9000),
            'status' => AiRequestStatus::Success,
            'error_message' => null,
        ];
    }

    public function failed(string $message = 'The AI service timed out.'): static
    {
        return $this->state(fn () => [
            'status' => AiRequestStatus::Error,
            'output_tokens' => 0,
            'error_message' => $message,
        ]);
    }
}
