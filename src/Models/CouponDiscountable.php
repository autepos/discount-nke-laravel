<?php

namespace Autepos\DiscountNkeLaravel\Models;

use Autepos\Discount\Contracts\Discountable;
use Autepos\DiscountNkeLaravel\Exceptions\NoCouponDiscountablePriceAccessException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CouponDiscountable extends Model implements Discountable
{
    use HasFactory;

    /**
     * {@inheritDoc}
     */
    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'coupon_id',
        'discountable_id',
        'discountable_type',
    ];

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Autepos\DiscountNkeLaravel\Database\Factories\CouponDiscountableFactory::new();
    }

    /**
     * Relation to coupon
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Relation to discountable
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function discountable()
    {
        return $this->morphTo();
    }

    /**
     * {@inheritDoc}
     */
    public function getDiscountableIdentifier(): int
    {
        return $this->discountable_id;
    }

    /**
     * {@inheritDoc}
     */
    public function getDiscountableType(): string
    {
        return $this->discountable_type;
    }

    /**
     * {@inheritDoc}
     */
    public function getDiscountableItemPrice(): int
    {
        try {
            return (new $this->discountable_type())->price;
        } catch (\Exception $e) {
            throw new NoCouponDiscountablePriceAccessException(null, 0, $e);
        }
    }
}
