<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentAllocation extends Model
{
    protected $fillable = [
        'payment_id',
        'installment_id',
        'penalty_id',
        'principal_amount',
        'interest_amount',
        'penalty_amount',
    ];

    protected $casts = [
        'principal_amount' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function installment()
    {
        return $this->belongsTo(Installment::class);
    }
    
    public function penalty()
    {
        return $this->belongsTo(Penality::class);
    }
}