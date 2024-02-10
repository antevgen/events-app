<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RecurrentFrequency;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startAt = $this->faker->dateTimeThisMonth();
        $endAt = $this->faker->dateTimeBetween($startAt, Carbon::create($startAt)->endOfDay());

        return [
            'title' => $this->faker->realText(50),
            'description' => $this->faker->optional()->text(),
            'starts_at' => $startAt,
            'ends_at' => $endAt,
            'recurrent' => false,
        ];
    }

    public function recurrent(): static
    {
        return $this->state(function (array $attributes) {
            $frequencies = RecurrentFrequency::values();
            $frequency = $frequencies[random_int(0, count($frequencies) - 1)];
            $interval = RecurrentFrequency::from($frequency)->interval();
            $repeatUntil = $this->faker->dateTimeBetween(
                Carbon::parse($attributes['ends_at'])->add($interval, 2),
                Carbon::parse($attributes['ends_at'])->add($interval, random_int(3, 10)),
            );

            return [
                'recurrent' => true,
                'frequency' => $frequency,
                'repeat_until' => $repeatUntil,
            ];
        });
    }
}
