<?php

namespace Autepos\DiscountNkeLaravel\Models;

use Autepos\Discount\Contracts\DiscountInstrument;
use Autepos\Discount\DiscountLineItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PromotionCode extends Model implements DiscountInstrument
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

    /**
     * {@inheritDoc}
     */
    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'meta' => 'array',
    ];

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Autepos\DiscountNkeLaravel\Database\Factories\PromotionCodeFactory::new();
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
     * Relation with Discount.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function discounts()
    {
        return $this->hasMany(Discount::class);
    }

    /**
     * Scope for selecting only redeemable items.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRedeemable($query, $user_id = null)
    {
        return $query->where(function ($query) {
            $query->whereNull('expires_at')->orWhereDate('expires_at', '>=', Carbon::now());
        })
            ->where('status', static::STATUS_ACTIVE)
            ->where('times_redeemed', '<', 'max_redemptions')
            ->when($user_id, function ($query, $user_id) {
                $query->where(
                    function ($query) use ($user_id) {
                        $query->whereNull('user_id')->orWhereDate('user_id', $user_id);
                    }
                );
            });
    }

    /**
     * {@inheritDoc}
     */
    public function getDiscountInstrumentType(): string
    {
        return 'promo';
    }

    /**
     * {@inheritDoc}
     */
    public function getDiscountInstrumentIdentifier(): string
    {
        return $this->getKey();
    }

    /**
     * {@inheritDoc}
     */
    public function getDiscountType(): string
    {
        return $this->coupon->discount_type;
    }

    /**
     * {@inheritDoc}
     */
    public function getDiscountName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getDiscountables(): array
    {
        return $this->coupon->discountables->all();
    }

    /**
     * Check if the promotion code is active.
     */
    public function isActive(): bool
    {
        return $this->status == static::STATUS_ACTIVE;
    }

    /**
     * {@inheritDoc}
     */
    public function getAmountOff(): int
    {
        return intval($this->coupon->amount_off);
    }

    /**
     * {@inheritDoc}
     */
    public function getPercentOff(): float
    {
        return floatval($this->coupon->percent_off);
    }

    /**
     * {@inheritDoc}
     */
    public function getRestrictionsMinimumAmount(): int
    {
        return intval($this->restrictions_minimum_amount);
    }

    /**
     * {@inheritDoc}
     */
    public function getMinQuantity(): int
    {
        return intval($this->coupon->min_quantity);
    }

    /**
     * {@inheritDoc}
     */
    public function getMaxQuantity(): int|null
    {
        return is_null($this->coupon->max_quantity)
            ? null
            : intval($this->coupon->max_quantity);
    }

    /**
     * {@inheritDoc}
     */
    public function getUnitQuantity(): int|null
    {
        return is_null($this->coupon->unit_quantity)
            ? null
            : intval($this->coupon->unit_quantity);
    }

    /**
     * {@inheritDoc}
     */
    public function getFreeQuantity(): int
    {
        return intval($this->coupon->free_quantity);
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
     * Check if the promotion code has expired.
     */
    public function hasExpired(): bool
    {
        if ($this->expires_at and $this->expires_at->lt(Carbon::now())) {
            return true;
        }

        return $this->coupon->hasExpired();
    }

    /**
     * {@inheritDoc}
     */
    public function isRedeemable(
        int $count = 1,
        array $meta = [],
    ): bool {
        $general = (! $this->hasExpired()
            and $this->isActive()
            and ! $this->exceedsMaxRedemptions($count)
            and $this->coupon->isRedeemable($count));

        // Add the user, order and tenant specific checks
        $for_user = true;
        $for_order = true;
        $for_tenant = true;

        $user_id = $meta['user_id'] ?? null;
        $order_id = $meta['order_id'] ?? null;
        $admin_id = $meta['admin_id'] ?? null;
        $tenant_id = $meta['tenant_id'] ?? null;

        // Check user whom this has been specifically given to
        if ($this->user_id) {
            if ($this->user_id != $user_id) {
                $for_user = false;
            }
        }

        // Check the order whom this applicable to
        if ($this->order_id) {
            if ($this->order_id != $order_id) {
                $for_order = false;
            }
        }

        // Check the tenant whom this applicable to
        if ($this->tenant_id) {
            if ($this->tenant_id != $tenant_id) {
                $for_tenant = false;
            }
        }

        // Return the result
        return $general and $for_user and $for_order and $for_tenant;
    }

    /**
     * {@inheritDoc}
     */
    public function getDiscountedPrice(): int
    {
        return $this->coupon->price;
    }

    /**
     * {@inheritDoc}
     */
    public function redeem(DiscountLineItem $discountLineItem): bool
    {
        DB::beginTransaction();

        // Multiple items can share the same discount, so we only need to
        // increment the times redeemed once.
        if ($discountLineItem->getUnitQuantityGroupNumber() == 1) {
            $this->coupon->redeem();
        }

        $meta = $discountLineItem->getMeta();
        $user_id = $meta['user_id'] ?? null;
        $order_id = $meta['order_id'] ?? null;
        $admin_id = $meta['admin_id'] ?? null;
        $tenant_id = $meta['tenant_id'] ?? $this->tenant_id;

        // Create the discount
        $discount = new Discount();
        $discount->start = Carbon::now();
        $discount->order_id = $order_id;
        $discount->user_id = $user_id;
        $discount->admin_id = $admin_id;
        $discount->tenant_id = $tenant_id;
        $discount->meta = $discountLineItem->getMeta();
        $discount->amount = $discountLineItem->getAmount();

        $discount->discountable_device_id = $discountLineItem->getDiscountLine()->getDiscountableDevice()->getDiscountableDeviceIdentifier();
        $discount->discountable_device_type = $discountLineItem->getDiscountLine()->getDiscountableDevice()->getDiscountableDeviceType();

        $discountableDeviceLine = $discountLineItem->getDiscountLine()->getDiscountableDeviceLine();
        $discount->discountable_device_line_id = $discountableDeviceLine->getDiscountableDeviceLineIdentifier();
        $discount->discountable_device_line_type = $discountableDeviceLine->getDiscountableDeviceLineType();

        $discount->discountable_id = $discountableDeviceLine->getDiscountable()->getDiscountableIdentifier();
        $discount->discountable_type = $discountableDeviceLine->getDiscountable()->getDiscountableType();

        $discount->unit_quantity = $discountLineItem->getUnitQuantity();
        $discount->unit_quantity_group = $discountLineItem->getUnitQuantityGroup();
        $discount->unit_quantity_group_number = $discountLineItem->getUnitQuantityGroupNumber();

        $discount->processor = $discountLineItem->getProcessor();

        $discount->coupon_id = $this->coupon->id;
        $this->discounts()->save($discount);

        if ($discount->exists) {
            $done = true;

            // Again multiple items can share the same discount, so we only need to
            // increment the times redeemed once.
            if ($discountLineItem->getUnitQuantityGroupNumber() == 1) {
                $this->times_redeemed++;
                $done = $this->save();
            }

            //
            if ($done) {
                DB::commit();

                return true;
            }
        }
        DB::rollBack();

        return false;
    }
}
