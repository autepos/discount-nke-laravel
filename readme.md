# Introduction
DiscountNkeLaravel is an implementation of autepos/discount for Laravel. It is designed to be similar to Stripe's Discount which is made up of a coupon and a promotion code. It not quite a wrapper around autepos/discount since it mainly implement the DiscountInstrument and interface and provides the necessary Eloquent models.

# Requirements
- PHP 8.0+
- Laravel 9.x+

# Installation
Install the package via composer:
```bash
composer require autepos/discount-nke-laravel

php artisan migrate
```

# Usage
```php
use Autepos\DiscountNkeLaravel\Contracts\DiscountProcessorFactory;
use Autepos\Discount\Contracts\DiscountableDevice;
use Autepos\DiscountNkeLaravel\Models\PromotionCode;

class Order implements DiscountableDevice
{
    //...
}


$discountableDevice = new Order();
$discountInstrument = new PromotionCode::find(1);

$processor = app(DiscountProcessorFactory::class);
$processor->addDiscountableDevice($discountableDevice)
          ->addDiscountInstrument($discountInstrument);
$discountLineList = $processor->calculate();

// Get the discount amount
$discountAmount = $discountLineList->amount();

// Persist the discount
$discountLineList->redeem();



