<?php

namespace Autepos\DiscountNkeLaravel\Tests\Feature;

use Autepos\Discount\Contracts\Discountable;
use Autepos\Discount\Processors\LinearDiscountProcessor;
use Autepos\DiscountNkeLaravel\Models\Coupon;
use Autepos\DiscountNkeLaravel\Models\CouponDiscountable;
use Autepos\DiscountNkeLaravel\Models\PromotionCode;
use Autepos\DiscountNkeLaravel\Tests\Fixtures\DiscountableDeviceFixture;
use Autepos\DiscountNkeLaravel\Tests\Fixtures\DiscountableFixture;
use Autepos\DiscountNkeLaravel\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

class CouponTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prepare discount processor
     */
    private function prepareLinearDiscountProcessor(): LinearDiscountProcessor
    {
        // Create discount instrument
        $promotionCode = PromotionCode::factory()
        ->for(Coupon::factory())
        ->create();

        // Create discountable device
        $discountableDevice = new DiscountableDeviceFixture(1, null, 5000);

        $processor = $this->discountManager()->processor(LinearDiscountProcessor::PROCESSOR);
        $processor->addDiscountInstrument($promotionCode);
        $processor->addDiscountableDevice($discountableDevice);
        $processor->setOrderId(1);

        return $processor;
    }

    /**
     * Test that used coupon cannot be redeemed
     */
    public function testUsedCouponCannotBeRedeemed(): void
    {
        // Prepare discount processor
        $processor = $this->prepareLinearDiscountProcessor();
        $promotionCode = $processor->getDiscountInstruments()[0];
        $coupon = $promotionCode->coupon;
        $coupon->times_redeemed = $coupon->max_redemptions;
        $coupon->save();

        $discountLineList = $processor->calculate();
        $this->assertEquals(0, $discountLineList->count());
    }

    /**
     * Test that inactive coupon cannot be redeemed
     */
    public function testInactiveCouponCannotBeRedeemed(): void
    {
        // Prepare discount processor
        $processor = $this->prepareLinearDiscountProcessor();
        $promotionCode = $processor->getDiscountInstruments()[0];
        $coupon = $promotionCode->coupon;
        $coupon->status = Coupon::STATUS_INACTIVE;
        $coupon->save();

        $discountLineList = $processor->calculate();
        $this->assertEquals(0, $discountLineList->count());
    }

    /**
     * Test that expired coupon cannot be redeemed
     */
    public function testExpiredCouponCannotBeRedeemed(): void
    {
        // Prepare discount processor
        $processor = $this->prepareLinearDiscountProcessor();
        $promotionCode = $processor->getDiscountInstruments()[0];
        $coupon = $promotionCode->coupon;
        $coupon->expires_at = Carbon::yesterday();
        $coupon->save();

        $discountLineList = $processor->calculate();
        $this->assertEquals(0, $discountLineList->count());
    }

    /**
     * Test coupon discountable can be created
     */
    public function testCouponDiscountableCanBeCreated(): void
    {
        // Created coupon
        $coupon = Coupon::factory()->create();

        // Create discountable
        CouponDiscountable::unguard();
        $discountable = $coupon->discountables()->create([
            'discountable_id' => 1,
            'discountable_type' => DiscountableFixture::class,
        ]);

        // Assert discountable exists in database
        $this->assertTrue($discountable->exists);

        // Assert discountable is associated with coupon
        $this->assertEquals($coupon->id, $discountable->coupon_id);

        // Assert discountable implements Discountable interface
        $this->assertInstanceOf(Discountable::class, $discountable);
        $this->assertInstanceOf(Discountable::class,
            $coupon->discountables()->where($discountable->getTable().'.id', $discountable->id)->first()
        );
    }
}
