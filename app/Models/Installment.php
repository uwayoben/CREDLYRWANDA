<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Installment extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'loan_id',
        'installment_number',
        'due_date',
        'principal_due',
        'interest_due',
        'total_due',
        'penalty_due',
        'status',
        'paid_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'due_date' => 'date',
        'paid_date' => 'date',
        'principal_due' => 'decimal:2',
        'interest_due' => 'decimal:2',
        'total_due' => 'decimal:2',
        'penalty_due' => 'decimal:2',
    ];

    /**
     * Get the loan that owns the installment.
     */
    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Get the payment allocations for the installment.
     */
    public function paymentAllocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /**
     * Get the penalties for the installment.
     */
    public function penalties()
    {
        return $this->hasMany(Penality::class);
    }
}
