<?php

namespace Autepos\DiscountNkeLaravel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

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
