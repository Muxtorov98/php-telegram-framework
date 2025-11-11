<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'order';
    protected $fillable = [
        'chat_id',
        'product_name',
        'quantity',
        'address',
        'status',
    ];
}