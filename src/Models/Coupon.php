<?php

namespace Autepos\DiscountNkeLaravel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Coupon model.
 * 
 * @property int $id Model id.
 * @property string|null $tenant_id The tenant id.
 * @property string|null $admin The admin that created the coupon.
 * @property int $min_quantity The minimum quantity of the products in the basket.
 * @property int|null $unit_quantity The quantity of the product that is discounted as a unit. Set to null to apply discount to all available products.
 * @property string $discount_type The discount type. Allowed values: amount_off, percent_off, buy_n_for_price_of_m, buy_n_for_price.
 * @property int|null $amount_off The amount off. Overrides percent_off.
 * @property double|null $percent_off The percent off.
 * @property int|null $free_quantity Buy the quantity stated in the unit_quantity for the the price of a quantity stated here. e.g buy 3 for the price of 2, where 3 is  the unit_quantity and 1 is the free quantity.
 * @property int|null $price Buy the quantity stated in the unit_quantity for a price stated here. e.g buy 3 for £25, where 3 is  the unit_quantity and 2500 is the price.
 * @property int|null $duration_in_months The duration in months.
 * @property string $name The coupon name.
 * @property int $max_redemptions The maximum number of times the coupon can be redeemed.
 * @property Carbon $expires_at The date and time the coupon expires.
 * @property int $times_redeemed The number of times the coupon has been redeemed.
 * @property string|null $description Description of the type of coupon. Allowed values: discount, free_shipping.
 * @property string $status The coupon status. Allowed values: active, inactive.
 * @property array|null $meta The coupon meta.
 * @property Carbon $created_at The date and time the coupon was created.
 * @property Carbon $updated_at The date and time the coupon was updated.
 * @property-read \Illuminate\Database\Eloquent\Collection|\Autepos\DiscountNkeLaravel\Models\CouponDiscountable[] $discountables
 */
class Coupon extends Model
{
    use HasFactory;

    /**
     * Status string for active
     */
    public const STATUS_ACTIVE = 'active';

    /**
     * Status string for inactive
     */
    public const STATUS_INACTIVE = 'inactive';

    protected $casts = [
        'expires_at' => 'datetime',
        'meta' => 'array',
    ];

    /**
     * {@inheritDoc}
     */
    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'expires_at',
    ];

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Autepos\DiscountNkeLaravel\Database\Factories\CouponFactory::new();
    }

    /**
     * Relationship with discountables.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function discountables()
    {
        return $this->hasMany(CouponDiscountable::class);
    }

    /**
     * Mutator for amount_off attribute
     *
     * @return void
     */
    public function setAmountOffAttribute(?int $value)
    {
        $this->attributes['amount_off'] = $value;
    }

    /**
     *  Check if coupon is active
     */
    public function isActive(): bool
    {
        return $this->status == static::STATUS_ACTIVE;
    }

    /**
     * Check of the max redemption count has been reached.
     *
     * @param  int  $additional_redemptions Additional redemptions to add to the current redemption count.
     * @return bool
     */
    public function exceedsMaxRedemptions(int $additional_redemptions = 0)
    {
        return $this->times_redeemed + $additional_redemptions > $this->max_redemptions;
    }

    /**
     * Check if coupon has expired.
     */
    public function hasExpired(): bool
    {
        if ($this->expires_at) {
            return $this->expires_at->lt(Carbon::now());
        }

        return false;
    }

    /**
     * Check if coupon can be redeemed.
     *
     * @param  int  $count Number of times to redeem.
     */
    public function isRedeemable(int $count = 1): bool
    {
        return
            ! $this->hasExpired()
            and $this->isActive()
            and ! $this->exceedsMaxRedemptions($count);
    }

    /**
     * Redeem the coupon
     */
    public function redeem(): bool
    {
        $this->times_redeemed = $this->times_redeemed + 1;

        return $this->save();
    }
}
