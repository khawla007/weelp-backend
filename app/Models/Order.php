<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'orderable_type', 'orderable_id', 'item_snapshot_json',
        'travel_date', 'preferred_time', 'number_of_adults',
        'number_of_children', 'status', 'special_requirements'
    ];

    public function orderable()
    {
        return $this->morphTo();
    }

    public function payment()
    {
        return $this->hasOne(OrderPayment::class);
    }

    public function emergencyContact()
    {
        return $this->hasOne(OrderEmergencyContact::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function review()
    {
        return $this->hasOne(\App\Models\Review::class, 'item_id', 'orderable_id')
            ->where('user_id', $this->user_id)
            ->where('item_type', strtolower(class_basename($this->orderable_type)));
    }

}

