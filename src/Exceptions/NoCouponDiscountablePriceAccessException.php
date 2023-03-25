<?php

namespace Autepos\DiscountNkeLaravel\Exceptions;

use Autepos\Discount\Contracts\DiscountableDeviceLine;

/**
 * This exception is thrown when it is not possible to get a discountable's
 * price through a coupon discountable.
 */
class NoCouponDiscountablePriceAccessException extends \Exception implements ExceptionInterface
{
    /**
     * Default message
     */
    private const DEFAULT_MESSAGE = 'Discountable price not found. Access price property on 
                discountable model provided by the method '
                .DiscountableDeviceLine::class.'::getDiscountable()';

    public function __construct($message = '', $code = 0, \Throwable $previous = null)
    {
        $message = $message ?: self::DEFAULT_MESSAGE;
        parent::__construct($message, $code, $previous);
    }
}
