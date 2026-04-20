<?php

namespace App\Service;

use App\DTO\MailDTO;
use App\Service\Api\ApiClient;


class MailService
{
    public function __construct(
        private ApiClient $apiClient
    ) {}

    public function sendMail(MailDTO $mailData): array
    {
        $mail = $mailData->toArray();
        $mail['eidas'] = 0;

        return $this->apiClient->postRequest(
            'api/mail',
            $mail
        );
    }

    public function getMailInfo(string $mailId): array
    {
        $mailId = trim($mailId);

        if (empty($mailId)) {
            throw new \InvalidArgumentException('Mail ID is required');
        }

        return $this->apiClient->getRequest("api/mail", ['id' => $mailId]);
    }
}
