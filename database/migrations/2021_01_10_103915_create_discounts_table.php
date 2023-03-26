<?php

use Autepos\DiscountNkeLaravel\Models\Discount;
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
        Schema::create((new Discount())->getTable(), function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('coupon_id');
            $table->unsignedBigInteger('promotion_code_id');
            $table->nullableMorphs('discountable_device', 'autepos_discountable_device');
            $table->nullableMorphs('discountable_device_line', 'autepos_discountable_device_line');
            $table->nullableMorphs('discountable', 'autepos_discountable'); //This is the product that is discounted
            $table->string('order_id')->nullable();
            $table->string('user_id')->nullable();
            $table->string('admin_id')->nullable();
            $table->string('tenant_id')->nullable();
            $table->timestamp('start')->nullable();
            $table->timestamp('end')->nullable();
            $table->unsignedInteger('unit_quantity')->default(1); // @see /Autepos/Discount/DiscountLineItem::unitQuantity
            $table->string('unit_quantity_group')->default('none'); //This is the tag that identifies a group/chunk that are discounted together. @see /Autepos/Discount/DiscountLineItem::unitQuantityGroup
            $table->unsignedSmallInteger('unit_quantity_group_number')->default(1); //@see /Autepos/Discount/DiscountLineItem::unitQuantityGroupNumber
            $table->unsignedBigInteger('amount');
            $table->string('processor');
            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists((new Discount())->getTable());
    }
};
