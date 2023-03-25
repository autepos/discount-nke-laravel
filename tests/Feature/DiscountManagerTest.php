<?php

namespace Autepos\DiscountNkeLaravel\Tests\Feature;

use Autepos\Discount\Processors\LinearDiscountProcessor;
use Autepos\DiscountNkeLaravel\DiscountManager;
use Autepos\DiscountNkeLaravel\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DiscountManagerTest extends TestCase
{
    use RefreshDatabase;

    // Test that the discount manager instance can be resolved
    public function testDiscountManagerCanBeResolved()
    {
        $this->assertInstanceOf(
            DiscountManager::class,
            $this->discountManager()
        );
    }

    /**
     * Test that the discount processor instance can be resolved.
     */
    public function testDiscountProcessorCanBeResolved()
    {
        $discountProcessor = $this->discountManager()->processor(LinearDiscountProcessor::PROCESSOR);
        $this->assertInstanceOf(
            LinearDiscountProcessor::class,
            $discountProcessor
        );
    }
}
