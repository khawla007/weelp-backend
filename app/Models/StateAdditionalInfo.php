<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $state_id
 * @property string $title
 * @property string $content
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\State $state
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateAdditionalInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateAdditionalInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateAdditionalInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateAdditionalInfo whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateAdditionalInfo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateAdditionalInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateAdditionalInfo whereStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateAdditionalInfo whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateAdditionalInfo whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class StateAdditionalInfo extends Model
{
    use HasFactory;

    // protected $table = 'state_additional_info';
    protected $fillable = [
        'state_id',
        'title',
        'content',
    ];

    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
