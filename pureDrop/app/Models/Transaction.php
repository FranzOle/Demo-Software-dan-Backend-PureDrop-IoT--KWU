<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    protected $fillable = [
        'customer_name',
        'liter',
        'price',
        'order_id',
        'payment_status',
        'payment_type',
        'transaction_time',
    ];

    protected $casts = [
        'liter' => 'float',
        'price' => 'integer',
        'transaction_time' => 'datetime',
    ];

    public function scopeSuccess($query)
    {
        return $query->where('payment_status', 'success');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', now()->toDateString());
    }

    public static function generateOrderId(): string
    {
        return 'WATER-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
    }

    public function setPaymentStatusAttribute($value)
    {
        $this->attributes['payment_status'] = strtolower($value);
    }
}
