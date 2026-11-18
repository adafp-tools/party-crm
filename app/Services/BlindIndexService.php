<?php

namespace App\Services;

class BlindIndexService
{
    public function compute(string $value): string
    {
        return hash_hmac(
            'sha256',
            mb_strtolower(trim($value)),
            (string) config('app.blind_index_key', '')
        );
    }
}
