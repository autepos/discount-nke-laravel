<?php

namespace Autepos\DiscountNkeLaravel\Database\Factories;

use Autepos\DiscountNkeLaravel\Models\PromotionCode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Autepos\DiscountNkeLaravel\Models\PromotionCode>
 */
class PromotionCodeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PromotionCode::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word(),
            'code' => $this->faker->unique()->word,
            'description' => $this->faker->sentence(),
            'times_redeemed' => 0,
            'max_redemptions' => 1,
            'expires_at' => Carbon::tomorrow(),
            'status' => PromotionCode::STATUS_ACTIVE,
        ];
    }
}
