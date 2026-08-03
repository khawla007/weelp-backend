<?php

namespace App\Support;

class SupportReferenceGenerator
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function generate(): string
    {
        $suffix = '';
        $lastIndex = strlen(self::ALPHABET) - 1;

        for ($index = 0; $index < 6; $index++) {
            $suffix .= self::ALPHABET[random_int(0, $lastIndex)];
        }

        return 'WLP-'.now()->format('ymd').'-'.$suffix;
    }
}
