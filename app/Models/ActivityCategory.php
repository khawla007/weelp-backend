<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityCategory extends Model {
    use HasFactory;

    protected $fillable = ['activity_id', 'category_id'];

    public function activity() {
        return $this->belongsTo(Activity::class);
    }

    public function category() {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
