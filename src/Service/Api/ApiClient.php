<?php

namespace App\Service\Api;

use App\Security\Decryptor;
use Psr\Log\LoggerInterface;
use App\Security\SignatureGenerator;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\TimeoutExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ApiClient
{
    public function __construct(
        private HttpClientInterface $apiClient,
        protected LoggerInterface $logger,
        protected SignatureGenerator $signatureGenerator,
        protected Decryptor $decryptor,
        #[Autowire('%api_token%')] private string $token
    ) {}

    public function getRequest(string $url, array $params = [], array $options = []): array
    {
        return $this->doRequest('GET', $url, $params, $options);
    }
    public function  postRequest(string $url, array $params = [], array $options = []): array
    {
        return $this->doRequest('POST', $url, $params, $options);
    }

    public function doRequest(string $method, string $url, array $params = [], array $options = []): array
    {
        try {
            $date = $this->getCurrentDateTime();
            $signature = $this->signatureGenerator->generate($date);

            $params = [
                ...$params,
                'token' => $this->token,
                'date' => $date,
            ];
            $options = [
                'headers' => [
                    'signature' => $signature,
                ],
            ];

            if ($method === 'GET') {
                $url = $this->getUrlRequest($url, $params);
            } else {
                $options['body'] = $params;
            }

            //dd($method, $url, $options);

            $encryptedResponse = $this->apiClient->request($method, $url, $options);
            $encryptedResponse = $encryptedResponse->getContent(false);
            $data = json_decode($encryptedResponse, true);

            if (($data['status'] ?? null) === 'ERROR') {
                throw new \RuntimeException($data['message'] ?? 'Unknown AR24 error');
            }

            $response = $this->decryptor->decrypt($encryptedResponse, $date);

            return $response;
        } catch (TimeoutExceptionInterface $e) {
            $this->logger->error(
                '[ApiClient Error] Request timeout',
                [
                    'exception' => $e->getMessage(),
                    'method' => $method,
                    'url' => $url,
                    'params' => $params,
                ]
            );

            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error(
                '[ApiClient Error] Cannot access to API',
                [
                    'exception' => $e->getMessage(),
                    'method' => $method,
                    'url' => $url,
                    'params' => $params,
                ]
            );

            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    protected function getUrlRequest(string $url, array $params): string
    {
        if ([] === $params) {
            return $url;
        }

        return $url . '?' . http_build_query($params);
    }

    private function getCurrentDateTime(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')))
            ->modify('+3 minutes')
            ->format('Y-m-d H:i:s');
    }
}
