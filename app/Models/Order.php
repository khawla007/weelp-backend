<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
/**
 * @property int $id
 * @property int $user_id
 * @property string $orderable_type
 * @property int $orderable_id
 * @property string|null $item_snapshot_json
 * @property string $travel_date
 * @property string|null $preferred_time
 * @property int|null $number_of_adults
 * @property int|null $number_of_children
 * @property string $status
 * @property string|null $special_requirements
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OrderEmergencyContact|null $emergencyContact
 * @property-read Model|\Eloquent $orderable
 * @property-read \App\Models\OrderPayment|null $payment
 * @property-read \App\Models\Review|null $review
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereItemSnapshotJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereNumberOfAdults($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereNumberOfChildren($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereOrderableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereOrderableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePreferredTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereSpecialRequirements($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereTravelDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereUserId($value)
 * @mixin \Eloquent
 */
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

