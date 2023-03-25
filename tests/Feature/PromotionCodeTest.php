<?php

namespace Autepos\DiscountNkeLaravel\Tests\Feature;

use Autepos\Discount\Contracts\Discountable;
use Autepos\Discount\Processors\LinearDiscountProcessor;
use Autepos\DiscountNkeLaravel\Models\Coupon;
use Autepos\DiscountNkeLaravel\Models\Discount;
use Autepos\DiscountNkeLaravel\Models\PromotionCode;
use Autepos\DiscountNkeLaravel\Tests\Fixtures\DiscountableDeviceFixture;
use Autepos\DiscountNkeLaravel\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

class PromotionCodeTest extends TestCase
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

        // Create discountable device which can be an order or a cart
        $order = new DiscountableDeviceFixture(1, null, 5000);

        $processor = $this->discountManager()->processor(LinearDiscountProcessor::PROCESSOR);
        $processor->addDiscountInstrument($promotionCode);
        $processor->addDiscountableDevice($order);
        $processor->setOrderId($order->getDiscountableDeviceIdentifier());

        return $processor;
    }

    // Test discount can be calculated
    public function testDiscountCanBeCalculated()
    {
        $discountLineList = $this->prepareLinearDiscountProcessor()->calculate();
        $this->assertEquals(10, $discountLineList->amount());
    }

    /**
     * Discount can be redeemed
     */
    public function testDiscountCanBeRedeemed(): void
    {
        $processor = $this->prepareLinearDiscountProcessor();
        $meta_key = 'test_key';
        $meta_value = 'test_value';
        $processor->setMeta([$meta_key => $meta_value]);
        $discountLineList = $processor->calculate();

        $this->assertTrue($discountLineList->redeem());

        // Check if discount has been redeemed
        foreach ($discountLineList as $discountLine) {
            foreach ($discountLine->items() as $discountLineItem) {
                $this->assertTrue($discountLineItem->isRedeemed());
            }
        }

        // Count the number of items in the discounts table
        $this->assertEquals(1, Discount::query()->count());

        // Check if discount has been stored correctly in db
        $expected = [
            'coupon_id' => $discountLineItem->getDiscountInstrument()->coupon->id,
            'promotion_code_id' => $discountLineItem->getDiscountInstrument()->getDiscountInstrumentIdentifier(),
            'order_id' => $discountLineItem->getOrderId(),
            'discountable_device_id' => $discountLine->getDiscountableDevice()->getDiscountableDeviceIdentifier(),
            'discountable_device_type' => $discountLine->getDiscountableDevice()->getDiscountableDeviceType(),
            'discountable_device_line_id' => $discountLine->getDiscountableDeviceLine()->getDiscountableDeviceLineIdentifier(),
            'discountable_device_line_type' => $discountLine->getDiscountableDeviceLine()->getDiscountableDeviceLineType(),
            'discountable_id' => $discountLine->getDiscountableDeviceLine()->getDiscountable()->getDiscountableIdentifier(),
            'discountable_type' => $discountLine->getDiscountableDeviceLine()->getDiscountable()->getDiscountableType(),
            'user_id' => $discountLineItem->getUserId(),
            'admin_id' => $discountLineItem->getAdminId(),
            'tenant_id' => $discountLineItem->getTenantId(),
            'unit_quantity' => $discountLineItem->getUnitQuantity(),
            'unit_quantity_group' => $discountLineItem->getUnitQuantityGroup(),
            'unit_quantity_group_number' => $discountLineItem->getUnitQuantityGroupNumber(),
            'amount' => $discountLineItem->getAmount(),
            "meta->$meta_key" => $meta_value,
            'processor' => $discountLineItem->getProcessor(),

        ];
        foreach ($discountLineList as $discountLine) {
            foreach ($discountLine->items() as $discountLineItem) {
                $this->assertDatabaseHas((new Discount)->getTable(), $expected);
            }
        }

        // Get the discount from db which matches the expected data
        $discount = Discount::query()
        ->select('*')
        ->where($expected)
        ->first();
        $this->assertEquals(
            $discount->start->format('Y-m-d'),
            Carbon::now()->format('Y-m-d')
        );
        $this->assertNull($discount->end);

        // Check that the promotion code times_redeemed has been incremented
        $promotionCode = PromotionCode::query()
        ->find($discountLineItem->getDiscountInstrument()->getDiscountInstrumentIdentifier());
        $this->assertEquals(1, $promotionCode->times_redeemed);

        // Check that the coupon times_redeemed has been incremented
        $coupon = Coupon::query()
        ->find($discountLineItem->getDiscountInstrument()->coupon->id);
        $this->assertEquals(1, $coupon->times_redeemed);
    }

    // Test that there is one redemption per unit discount.
    public function testThereIsOneRedemptionPerUnitDiscount(): void
    {
        // Prepare discount processor
        $processor = $this->prepareLinearDiscountProcessor();

        // Get the discountable device line
        $discountableDeviceLine = $processor->getDiscountableDevices()[0]->getDiscountableDeviceLines()[0];
        $discountableDeviceLine->quantity = 2;

        // Set the unit quantity
        $promotionCode = $processor->getDiscountInstruments()[0];
        $promotionCode->max_redemptions = 1000;
        $promotionCode->times_redeemed = 0;
        $promotionCode->save();
        $coupon = $promotionCode->coupon;
        $coupon->unit_quantity = 2;
        $coupon->max_redemptions = 1000;
        $coupon->times_redeemed = 0;
        $coupon->save();

        $processor->calculate()->redeem();
        $this->assertEquals(1, $promotionCode->refresh()->times_redeemed);
        $this->assertEquals(1, $promotionCode->coupon->times_redeemed);
    }

    /**
     * Test that expired promotion code cannot be redeemed
     */
    public function testExpiredPromotionCodeCannotBeRedeemed(): void
    {
        // Prepare discount processor
        $processor = $this->prepareLinearDiscountProcessor();
        $promotionCode = $processor->getDiscountInstruments()[0];
        $promotionCode->expires_at = Carbon::yesterday();
        $promotionCode->save();

        $discountLineList = $processor->calculate();
        $this->assertEquals(0, $discountLineList->count());
    }

    /**
     * Test that inactive promotion code cannot be redeemed
     */
    public function testInactivePromotionCodeCannotBeRedeemed(): void
    {
        // Prepare discount processor
        $processor = $this->prepareLinearDiscountProcessor();
        $promotionCode = $processor->getDiscountInstruments()[0];
        $promotionCode->status = PromotionCode::STATUS_INACTIVE;
        $promotionCode->save();

        $discountLineList = $processor->calculate();
        $this->assertEquals(0, $discountLineList->count());
    }

    /**
     * Test that used promotion code cannot be redeemed
     */
    public function testUsedPromotionCodeCannotBeRedeemed(): void
    {
        // Prepare discount processor
        $processor = $this->prepareLinearDiscountProcessor();
        $promotionCode = $processor->getDiscountInstruments()[0];
        $promotionCode->times_redeemed = $promotionCode->max_redemptions;
        $promotionCode->save();

        $discountLineList = $processor->calculate();
        $this->assertEquals(0, $discountLineList->count());
    }

    /**
     * Test that a user specific promotion code cannot be redeemed by a different user
     */
    public function testUserSpecificPromotionCodeCanOnlyBeRedeemedByCorrectUser(): void
    {
        // Try with the wrong user
        $processor = $this->prepareLinearDiscountProcessor();
        $promotionCode = $processor->getDiscountInstruments()[0];
        $promotionCode->user_id = 2;
        $promotionCode->save();

        $discountLineList = $processor->setUserId(1)->calculate();
        $this->assertEquals(0, $discountLineList->count());

        // Now try with the correct user
        $processor = $this->prepareLinearDiscountProcessor();
        $promotionCode = $processor->getDiscountInstruments()[0];
        $promotionCode->user_id = 1;
        $promotionCode->save();

        $discountLineList = $processor->setUserId(1)->calculate();
        $this->assertEquals(1, $discountLineList->count());
    }

    /**
     * Test that an order specific promotion code cannot be redeemed by a different order
     */
    public function testOrderSpecificPromotionCodeCanOnlyBeRedeemedByCorrectOrder(): void
    {
        // Try with the wrong order
        $processor = $this->prepareLinearDiscountProcessor();
        $promotionCode = $processor->getDiscountInstruments()[0];
        $promotionCode->order_id = 2;
        $promotionCode->save();

        $discountLineList = $processor->setOrderId(1)->calculate();
        $this->assertEquals(0, $discountLineList->count());

        // Now try with the correct order
        $processor = $this->prepareLinearDiscountProcessor();
        $promotionCode = $processor->getDiscountInstruments()[0];
        $promotionCode->order_id = 1;
        $promotionCode->save();

        $discountLineList = $processor->setOrderId(1)->calculate();
        $this->assertEquals(1, $discountLineList->count());
    }

    /**
     * Test that a tenant specific promotion code cannot be redeemed by a different tenant
     */
    public function testTenantSpecificPromotionCodeCanOnlyBeRedeemedByCorrectTenant(): void
    {
        // Try with the wrong tenant
        $processor = $this->prepareLinearDiscountProcessor();
        $promotionCode = $processor->getDiscountInstruments()[0];
        $promotionCode->tenant_id = 2;
        $promotionCode->save();

        $discountLineList = $processor->setTenantId(1)->calculate();
        $this->assertEquals(0, $discountLineList->count());

        // Now try with the correct tenant
        $processor = $this->prepareLinearDiscountProcessor();
        $promotionCode = $processor->getDiscountInstruments()[0];
        $promotionCode->tenant_id = 1;
        $promotionCode->save();

        $discountLineList = $processor->setTenantId(1)->calculate();
        $this->assertEquals(1, $discountLineList->count());
    }

    /**
     * Test that discountables can be retrieved.
     */
    public function testDiscountablesCanBeRetrieved(): void
    {
        // Create discount instrument/promotion code
        $promotionCode = PromotionCode::factory()
        ->for(Coupon::factory())
        ->create();

        // Create a couple ofdiscountables
        $promotionCode->coupon->discountables()->create([
            'discountable_type' => 'App\\Models\\HypotheticalProduct',
            'discountable_id' => 111,
        ]);

        $promotionCode->coupon->discountables()->create([
            'discountable_type' => 'App\\Models\\HypotheticalProduct',
            'discountable_id' => 212,
        ]);

        // Retrieve discountable
        $discountables = $promotionCode->getDiscountables();
        $this->assertEquals(2, count($discountables));

        // Check that the discountables match the expected data
        $this->assertEquals('App\\Models\\HypotheticalProduct', $discountables[0]['discountable_type']);
        $this->assertEquals(111, $discountables[0]['discountable_id']);
        $this->assertEquals('App\\Models\\HypotheticalProduct', $discountables[1]['discountable_type']);
        $this->assertEquals(212, $discountables[1]['discountable_id']);

        // Check that each discountable implements the Discountable interface
        foreach ($discountables as $discountable) {
            $this->assertInstanceOf(Discountable::class, $discountable);
        }
    }
}
