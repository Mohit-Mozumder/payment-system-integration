<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'amount',
        'transaction_id',
        'status',
        'refunded_at',
        'refund_transaction_id'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'refunded_at' => 'datetime'
    ];

    protected $attributes = [
        'status' => 'completed'
    ];
}