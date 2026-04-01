<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'creator_id', 'order_id', 'commission_rate', 'commission_amount', 'status',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
