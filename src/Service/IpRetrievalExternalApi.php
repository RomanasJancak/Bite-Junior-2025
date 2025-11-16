<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class IpRetrievalExternalApi
{
  private HttpClientInterface $httpClient;
  public function __construct(HttpClientInterface $httpClient){
    $this->httpClient = $httpClient;
  }
  public function fetchData(string $ip) : array
  {
    $apiKey = $_ENV['IPSTACK_API_KEY'];
    $response = $this->httpClient->request(
      'GET',
      "http://api.ipstack.com/{$ip}?access_key={$apiKey}"
    );

    if ($response->getStatusCode() !== 200) {
      throw new \Exception('Failed to fetch IP data');
    }

    $data = $response->toArray();
    
    return $data;
  }
}