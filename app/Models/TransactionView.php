<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionView extends Model
{
    protected $table = 'transactions_view';

    public $timestamps = false;

    protected $fillable = [];

    // View is read-only
    public static function boot()
    {
        parent::boot();
        static::creating(fn () => false);
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }
}
