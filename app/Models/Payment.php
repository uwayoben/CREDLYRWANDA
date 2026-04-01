<?php
// app/Models/Payment.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'company_id', 'loan_id', 'customer_id', 'received_by',
        'receipt_number', 'amount', 'payment_date', 'payment_method',
        'principal_paid', 'interest_paid', 'penalty_paid', 'advance_paid',
        'notes', 'transaction_reference', 'attachment'
    ];

    protected $casts = [
        'payment_date' => 'date'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    // Ensure principal_paid + interest_paid + penalty_paid <= amount
    public function validateAllocation()
    {
        $totalAllocated = $this->principal_paid + $this->interest_paid +
                          $this->penalty_paid + $this->advance_paid;

        return $totalAllocated <= $this->amount;
    }
}
