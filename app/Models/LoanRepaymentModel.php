<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LoanRepaymentModel extends Model
{
    use HasFactory;

    protected static function boot()
    {
        // Boot other traits on the Model
        parent::boot();

        /**
         * Listen for the creating event on the user model.
         * Sets the 'id' to a UUID using Str::uuid() on the instance being created
         */

        static::creating(function ($model) {
            if ($model->getKey() === null) {
                $model->setAttribute($model->getKeyName(), Str::uuid()->toString());
            }
        });
    }

    protected $fillable = [
        "id",
        "user_id",
        "loan_id",
        "payment_status",
        "paid_amount",
        "term_count",
        "is_active",
        "created_at",
        "updated_at"
    ];

    protected $table = "loan_repayments";
    protected $primaryKey = 'id';
    protected $keyType = 'string';


    public function getIncrementing()
    {
        return false;
    }


    public function getKeyType()
    {
        return 'string';
    }

    protected $casts = [
        "id" => "string"
    ];
}
