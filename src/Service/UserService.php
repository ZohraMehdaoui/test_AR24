<?php

namespace App\Service;

use App\DTO\UserDTO;
use App\Service\Api\ApiClient;


class UserService
{
    public function __construct(
        private ApiClient $apiClient
    ) {}

    public function createUser(UserDTO $userData): array
    {
        $user = $userData->toArray();

        return $this->apiClient->postRequest(
            'api/user/',
            $user
        );
    }

    public function getUserInfo(string $userIdentifier): array
    {
        $isNumeric = false;
        $isMail = false;
        $userIdentifier = trim($userIdentifier ?? '', " '\"");

        if ($userIdentifier === '') {
            throw new \InvalidArgumentException('User identifier is required');
        }

        $params = [
            'email' => null,
            'id_user' => null,
        ];

        if (is_numeric($userIdentifier)) {
            $params['id_user'] = $userIdentifier;
            $isNumeric = true;
        } elseif ($this->isEmail($userIdentifier)) {
            $params['email'] = $userIdentifier;
            $isMail = true;
        }

        // Validates that it is either an email or an entire ID
        if (!$isMail && !$isNumeric) {
            throw new \InvalidArgumentException('User identifier must be a valid email or numeric ID');
        }

        try {

            return $this->apiClient->getRequest('api/user/', $params);
        } catch (\Exception $e) {
            throw new \Exception('Failed to retrieve user information', 0, $e);
        }
    }

    function isEmail(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
}
