<?php

use Autepos\DiscountNkeLaravel\Models\Coupon;
use Autepos\DiscountNkeLaravel\Models\PromotionCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create((new PromotionCode())->getTable(), function (Blueprint $table) {
            $table->id();
            $table->char('type',20)->default(PromotionCode::TYPE_PROMO);
            $table->string('tenant_id')->nullable();
            $table->string('admin_id')->nullable(); // the admin that created the promo code

            $table->string('code')->unique();
            $table->string('name')->default('Promo');
            $table->unsignedBigInteger('coupon_id');

            $table->string('user_id')->nullable();
            $table->string('order_id')->nullable();
            $table->timestamp('expires_at');
            $table->unsignedBigInteger('max_redemptions')->default(1);

            $table->unsignedBigInteger('restrictions_minimum_amount')->default(0);

            $table->unsignedBigInteger('times_redeemed')->default(0);

            $table->string('description')->nullable();
            $table->string('status')->default(PromotionCode::STATUS_INACTIVE);
            $table->json('meta')->nullable();

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
        Schema::dropIfExists((new PromotionCode())->getTable());
    }
};
