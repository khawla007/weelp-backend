<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $package_id
 * @property string $question
 * @property string $answer
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Package $package
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageFaq newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageFaq newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageFaq query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageFaq whereAnswer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageFaq whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageFaq whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageFaq wherePackageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageFaq whereQuestion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageFaq whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PackageFaq extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id',
        // 'question_number',
        'question',
        'answer',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
