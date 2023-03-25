<?php

namespace Autepos\DiscountNkeLaravel\Contracts;

use Autepos\Discount\Processors\Contracts\DiscountProcessor;

interface DiscountProcessorFactory
{
    /**
     * Get a discount processor implementation.
     */
    public function processor(string $processor = null): DiscountProcessor;
}
