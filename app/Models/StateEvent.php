<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $state_id
 * @property string $name
 * @property array<array-key, mixed>|null $type
 * @property \Illuminate\Support\Carbon|null $date
 * @property string $location
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\State $state
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateEvent whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateEvent whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateEvent whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateEvent whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateEvent whereStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateEvent whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateEvent whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class StateEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'state_id',
        'name',
        'type',
        'date',
        'location',
        'description',
    ];

    protected $casts = [
        'type'     => 'array',
        // 'location' => 'array',
        'date'=> 'date:Y-m-d', 
    ];


    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
