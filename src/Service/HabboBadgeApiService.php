<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class HabboBadgeApiService
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @return array Returns badge array from API or empty array
     */
    public function getLatestStaffBadges(int $limit = 12): array
    {
        $url = sprintf('https://www.habboassets.com/api/v1/badges?limit=%d', $limit);
        try {
            $response = $this->client->request('GET', $url);
            if ($response->getStatusCode() !== 200) {
                return [];
            }
            $data = $response->toArray(false);
            return $data['badges'] ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
