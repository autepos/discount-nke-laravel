<?php

namespace Autepos\DiscountNkeLaravel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Discount model.
 * 
 * @property int $id The model id.
 * @property int $coupon_id The coupon id.
 * @property int $promotion_code_id The promotion code id.
 * @property int $discountable_device_id The discountable device id. An example of a discountable device is a cart or an order.
 * @property string $discountable_device_type The discountable device type. An example of a discountable device is a cart or an order.
 * @property int $discountable_device_line_id The discountable device line id. An example of a discountable device line is a cart line or an order line.
 * @property string $discountable_device_line_type The discountable device line type. An example of a discountable device line is a cart line or an order line.
 * @property string $discountable_id The discountable id. An example of a discountable is a product.
 * @property string $discountable_type The discountable type. An example of a discountable is a product.
 * @property string|null $order_id The order id.
 * @property string|null $user_id The user id. The user/customer that is using the discount.
 * @property string|null $admin_id The admin id. The admin that is using the discount.
 * @property string|null $tenant_id The tenant id. The tenant that is using the discount.
 * @property \DateTime|null $start The start date and time of the discount.
 * @property \DateTime|null $end The end date and time of the discount.
 * @property int $unit_quantity The unit quantity. This is the number of units that are discounted together. An example of a unit quantity is 3. This means that 3 units of the same product are discounted together.
 * @property string $unit_quantity_group The unit quantity group. This is the tag that identifies a group/chunk that are discounted together. An example of a unit quantity group is '3'. This means that 3 units of the same product are discounted together.
 * @property int $unit_quantity_group_number The unit quantity group number. This is the number of groups/chunks that are discounted together. An example of a unit quantity group number is 2. This means that 2 groups/chunks of 3 units of the same product are discounted together.
 * @property int $amount The amount of the discount.
 * @property string $processor The processor of the discount.
 * @property array|null $meta The arbitrary meta data of the discount.
 * @property \DateTime $created_at The created at date and time of the discount.
 * @property \DateTime $updated_at The updated at date and time of the discount.
 * 
 * 
 */
class Discount extends Model
{
    use HasFactory;

    /**
     * {@inheritDoc}
     */
    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
        'meta' => 'array',
    ];

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Autepos\DiscountNkeLaravel\Database\Factories\DiscountFactory::new();
    }
}
