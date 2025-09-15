<?php

namespace App\Models\user;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\products\BaseModel;
use App\Models\User;

class Order extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'email',
        'city',
        'address',
        'total_price',
        'status',
        'cancellation_reason',
        'return_status',   // processing | approved | rejected
        'return_reason',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function paymentMethod()
    {
        return $this->hasOne(PaymentMethod::class);
    }
}
