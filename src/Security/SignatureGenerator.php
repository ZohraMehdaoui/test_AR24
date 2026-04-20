<?php

namespace App\Security;

class SignatureGenerator
{
    public function __construct(
        private string $privateKey
    ) {}

    public function generate(string $date): string
    {
        $key = hash('sha256', $this->privateKey);
        $iv = mb_strcut(hash('sha256', hash('sha256', $this->privateKey)), 0, 16, 'UTF-8');
        $key = substr($key, 0, 32);

        return openssl_encrypt($date, 'aes-256-cbc', $key, false, $iv);
    }
}
