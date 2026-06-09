<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalScriptSetting extends Model
{
    protected $fillable = [
        'head_code',
        'body_code',
        'footer_code',
    ];

    public static function singleton(): self
    {
        $settings = self::query()->first();

        if ($settings) {
            return $settings;
        }

        $settings = new self();
        $settings->id = 1;
        $settings->save();

        return $settings;
    }
}
