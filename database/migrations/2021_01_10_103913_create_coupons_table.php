<?php

use Autepos\DiscountNkeLaravel\Models\Coupon;
use Autepos\DiscountNkeLaravel\Models\CouponDiscountable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCouponsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create((new Coupon())->getTable(), function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable();
            $table->string('admin')->nullable(); //The admin that created the coupon

            $table->unsignedInteger('min_quantity')->default(1); // The minimum quantity of the products in the basket.
            $table->unsignedInteger('unit_quantity')->nullable(); //The quantity of the product that is discounted as a unit. Set to null to apply discount to all available products.

            $table->string('discount_type')->default('amount_off'); // amount_off, percent_off, buy_n_for_price_of_m, buy_n_for_price
            $table->unsignedBigInteger('amount_off')->nullable();
            $table->double('percent_off')->nullable();
            $table->unsignedInteger('free_quantity')->nullable(); // Buy the quantity stated in the unit_quantity for the the price of a quantity stated here. e.g buy 3 for the price of 2, where 3 is  the unit_quantity and 1 is the free quantity
            $table->unsignedBigInteger('price')->nullable(); //Buy the quantity stated in the unit_quantity for a price stated here. e.g buy 3 for £25, where 3 is  the unit_quantity and 2500 is the price.

            $table->unsignedSmallInteger('duration_in_months')->nullable();
            $table->string('name')->default('Coupon');

            $table->unsignedBigInteger('max_redemptions')->default(1);

            $table->timestamp('expires_at');
            $table->unsignedBigInteger('times_redeemed')->default(0);
            $table->string('description')->nullable();
            $table->string('status')->default(Coupon::STATUS_INACTIVE);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create((new CouponDiscountable())->getTable(), function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('coupon_id');
            $table->morphs('discountable');

            /**
             * If true, the discountable will be included in the coupon.
             * If false, the discountable will be excluded from the coupon.
             * TODO: This is not yet implemented
             */
            $table->boolean('include')->default(true);

            //
            $table->timestamps();

            $table->foreign('coupon_id')
            ->references('id')
            ->on((new Coupon())->getTable())
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists((new Coupon())->getTable());
        Schema::dropIfExists((new CouponDiscountable())->getTable());
    }
}
