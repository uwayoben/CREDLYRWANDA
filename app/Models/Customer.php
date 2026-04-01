<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'names',
        'national_id',
        'date_of_birth',
        'gender',
        'province',
        'district',
        'sector',
        'cell',
        'village',
        'phone',
        'email',
        'marital_status',
        'employer_name',
        'employment_status',
        'relationship_with_ndfsp',
        'photo',
        'is_active',
                    'spouse_name',
'spouse_phone',
'spause_id_number',
'marital_property_regime'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the company that the customer belongs to.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the loans for the customer.
     */
    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * Get the payments made by the customer.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }












}
