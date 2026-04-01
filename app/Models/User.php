<?php

namespace App\Models;

use Filament\Auth\MultiFactor\Email\Concerns\InteractsWithEmailAuthentication;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


class User extends Authenticatable implements FilamentUser, HasEmailAuthentication, MustVerifyEmail
{
    use HasFactory, Notifiable, InteractsWithEmailAuthentication;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'phone',
        'position',
        'is_super_admin',
        'last_seen_at',
        'has_email_authentication',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_super_admin' => 'boolean',
        'has_email_authentication' => 'boolean',
    ];

    /**
     * Determine if the user can access the Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * Get the company that the user belongs to.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the loans created by the user.
     */
    public function createdLoans()
    {
        return $this->hasMany(Loan::class, 'created_by');
    }

    /**
     * Get the loans approved by the user.
     */
    public function approvedLoans()
    {
        return $this->hasMany(Loan::class, 'approved_by');
    }

    /**
     * Get the payments received by the user.
     */
    public function receivedPayments()
    {
        return $this->hasMany(Payment::class, 'received_by');
    }

    /**
     * Get the expenses created by the user.
     */
    public function createdExpenses()
    {
        return $this->hasMany(Expense::class, 'created_by');
    }

    /**
     * Get the other incomes received by the user.
     */
    public function receivedOtherIncomes()
    {
        return $this->hasMany(OtherIncome::class, 'received_by');
    }

    /**
     * Get the loan status changes performed by the user.
     */
    public function loanStatusChanges()
    {
        return $this->hasMany(LoanStatusHistory::class, 'changed_by');
    }

    /**
     * Get the penalties waived by the user.
     */
    public function waivedPenalties()
    {
        return $this->hasMany(Penality::class, 'waived_by');
    }

    /**
     * Check if user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin;
    }

    /**
     * Check if user is a loan officer.
     */
    public function isLoanOfficer(): bool
    {
        return $this->position === 'Loan Officer';
    }

    /**
     * Check if user is a managing director.
     */
    public function isManagingDirector(): bool
    {
        return $this->position === 'Managing Director';
    }

    /**
     * Check if user is a receptionist.
     */
    public function isReceptionist(): bool
    {
        return $this->position === 'Reception';
    }

    /**
     * Check if user is a shareholder.
     */
    public function isShareHolder(): bool
    {
        return $this->position === 'Share Holder';
    }

    /**
     * Scope a query to only include users of a given position.
     */
    public function scopeOfPosition($query, $position)
    {
        return $query->where('position', $position);
    }

    /**
     * Scope a query to only include loan officers.
     */
    public function scopeLoanOfficers($query)
    {
        return $query->where('position', 'Loan Officer');
    }

    /**
     * Scope a query to only include managing directors.
     */
    public function scopeManagingDirectors($query)
    {
        return $query->where('position', 'Managing Director');
    }

    /**
     * Scope a query to only include receptionists.
     */
    public function scopeReceptionists($query)
    {
        return $query->where('position', 'Reception');
    }

    /**
     * Scope a query to only include shareholders.
     */
    public function scopeShareHolders($query)
    {
        return $query->where('position', 'Share Holder');
    }

    /**
     * Get the user's position display name.
     */
    public function getPositionDisplayAttribute(): string
    {
        $positions = [
            'Loan Officer' => 'Loan Officer',
            'Managing Director' => 'Managing Director',
            'Reception' => 'Receptionist',
            'Share Holder' => 'Shareholder',
        ];

        return $positions[$this->position] ?? $this->position;
    }

    /**
     * Get the user's initials.
     */

}
