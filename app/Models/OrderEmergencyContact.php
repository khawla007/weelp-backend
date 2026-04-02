<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $order_id
 * @property string|null $contact_name
 * @property string|null $contact_phone
 * @property string|null $relationship
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Order $order
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderEmergencyContact newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderEmergencyContact newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderEmergencyContact query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderEmergencyContact whereContactName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderEmergencyContact whereContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderEmergencyContact whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderEmergencyContact whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderEmergencyContact whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderEmergencyContact whereRelationship($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderEmergencyContact whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class OrderEmergencyContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'contact_name', 'contact_phone', 'relationship',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
