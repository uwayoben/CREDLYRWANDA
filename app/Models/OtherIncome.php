<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtherIncome extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'received_by',
        'income_number',
        'income_date',
        'source',
        'description',
        'amount',
        'payment_method',
        'reference_number',
        'attachment',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'income_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the company that owns the other income.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who received the other income.
     */
    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
