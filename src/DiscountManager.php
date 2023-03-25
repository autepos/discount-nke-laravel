<?php

namespace Autepos\DiscountNkeLaravel;

use Autepos\Discount\Processors\Contracts\DiscountProcessor;
use Illuminate\Support\Manager;
use InvalidArgumentException;

class DiscountManager extends Manager implements Contracts\DiscountProcessorFactory
{
    /**
     * Get the default driver/processor name.
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function getDefaultDriver()
    {
        throw new InvalidArgumentException('No discount processor was specified.');
    }

    /**
     * {@inheritDoc}
     */
    public function processor(string $processor = null): DiscountProcessor
    {
        return parent::driver($processor);
    }
}
