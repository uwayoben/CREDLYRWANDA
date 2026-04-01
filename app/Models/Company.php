<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'registration_number',
        'email',
        'phone',
        'address',
        'province',
        'district',
        'sector',
        'cell',
        'village',
        'logo',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the users belonging to the company.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the customers belonging to the company.
     */
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get the loans belonging to the company.
     */
    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * Get the payments belonging to the company.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the expenses belonging to the company.
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Get the other incomes belonging to the company.
     */
    public function otherIncomes()
    {
        return $this->hasMany(OtherIncome::class);
    }

    /**
     * Get the full address attribute.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->village,
            $this->cell,
            $this->sector,
            $this->district,
            $this->province,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get the logo URL attribute.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logo) {
            return asset('storage/' . $this->logo);
        }

        return null;
    }

  


}
