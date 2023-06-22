<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanRequestModel extends Model
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

    protected $table = "loan_requests";

    protected $fillable = [
        "id",
        "user_id",
        "status",
        "amount",
        "interest_percentage",
        "term",
        "govt_id",
        "is_active",
        "created_at",
        "updated_at"

    ];

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

    protected function status(): Attribute
    {
        $reverseList = array_flip(config("constants.LOAN_STATES"));
        return Attribute::make(
            get: fn (string $value) => $reverseList[$value],
        );
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(LoanRepaymentModel::class,"loan_id","id");
    }
}
