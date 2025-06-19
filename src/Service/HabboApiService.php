<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class HabboApiService
{
    private HttpClientInterface $httpClient;
    private string $baseUrl = 'https://www.habbo.com.tr/api/public';

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Kullanıcı adına göre uniqueId alır
     */
    public function getUserUniqueId(string $username): ?string
    {
        try {
            $response = $this->httpClient->request('GET', "{$this->baseUrl}/users", [
                'query' => [
                    'name' => $username
                ]
            ]);

            $data = $response->toArray();
            return $data['uniqueId'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * UniqueId ile kullanıcı profil bilgilerini alır
     */
    public function getUserProfile(string $uniqueId): ?array
    {
        try {
            $response = $this->httpClient->request('GET', "{$this->baseUrl}/users/{$uniqueId}/profile");
            return $response->toArray();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Kullanıcı adından profil bilgilerini alır
     */
    public function getProfileByUsername(string $username): ?array
    {
        $uniqueId = $this->getUserUniqueId($username);

        if (!$uniqueId) {
            return null;
        }

        return $this->getUserProfile($uniqueId);
    }
}
