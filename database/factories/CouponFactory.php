<?php

namespace Autepos\DiscountNkeLaravel\Database\Factories;

use Autepos\Discount\DiscountTypes;
use Autepos\DiscountNkeLaravel\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CouponFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Coupon::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word(),
            'discount_type' => DiscountTypes::AMOUNT_OFF,
            'amount_off' => 10,
            'percent_off' => null,
            'description' => $this->faker->sentence(),
            'times_redeemed' => 0,
            'max_redemptions' => 1,
            'expires_at' => Carbon::tomorrow(),
            'status' => Coupon::STATUS_ACTIVE,

        ];
    }

    /**
     * Amount off discount
     */
    public function amountOff()
    {
        return $this->state(function (array $attributes) {
            return [
                'discount_type' => DiscountTypes::AMOUNT_OFF,
                'amount_off' => 10,
                'percent_off' => null,
            ];
        });
    }

    /**
     * Percentage off discount
     */
    public function percentOff()
    {
        return $this->state(function (array $attributes) {
            return [
                'discount_type' => DiscountTypes::PERCENT_OFF,
                'amount_off' => null,
                'percent_off' => 10,
            ];
        });
    }
}
