<?php

namespace Autepos\DiscountNkeLaravel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;

    /**
     * {@inheritDoc}
     */
    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
        'meta' => 'array',
    ];

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Autepos\DiscountNkeLaravel\Database\Factories\DiscountFactory::new();
    }
}
