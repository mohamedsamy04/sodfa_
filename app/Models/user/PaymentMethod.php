<?php

namespace App\Models\user;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\products\BaseModel;

class PaymentMethod extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'method', 'receipt_image', 'status'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
