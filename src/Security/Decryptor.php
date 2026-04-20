<?php

namespace App\Security;

class Decryptor
{
    public function __construct(
        private string $privateKey
    ) {}

    public function decrypt(string $encryptedResponse, string $date): array
    {
        // Generate key
        $key = hash('sha256', $date . $this->privateKey);

        // Generate IV (first 16 bytes)
        $iv = mb_strcut(
            hash('sha256', hash('sha256', $this->privateKey)),
            0,
            16,
            'UTF-8'
        );

        // Decrypt
        $decrypted = openssl_decrypt(
            base64_decode($encryptedResponse),
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypted === false) {
            throw new \RuntimeException('Decrypt failed');
        }

        // JSON decode
        $data = json_decode($decrypted, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON after decrypt');
        }

        return $data;
    }
}
