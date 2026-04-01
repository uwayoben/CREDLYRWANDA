<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penality extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'penalities';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'loan_id',
        'installment_id',
        'penalty_amount',
        'penalty_date',
        'reason',
        'is_waived',
        'waived_date',
        'waived_by',
        
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'penalty_date' => 'date',
        'waived_date' => 'date',
        'penalty_amount' => 'decimal:2',
        'is_waived' => 'boolean',
    ];

    /**
     * Get the loan that owns the penalty.
     */
    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Get the installment that owns the penalty.
     */
    public function installment()
    {
        return $this->belongsTo(Installment::class);
    }

    /**
     * Get the user who waived the penalty.
     */
    public function waivedBy()
    {
        return $this->belongsTo(User::class, 'waived_by');
    }
}
