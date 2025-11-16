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
    $url = $_ENV['IPSTACK_API_URL'] . "{$ip}?access_key={$apiKey}";
    $response = $this->httpClient->request(
      'GET',
      $url
    );
    //return $this->handleResponse($response);
    if ($response->getStatusCode() !== 200) {
      throw new \Exception('Failed to fetch IP data from external API');
    }

    $data = $response->toArray();
    
    return $data;
  }
}