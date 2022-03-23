<?php

namespace Drupal\swapi\Service;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

class SWApiService
{

  const API_ENDPOINT_PEOPLES = 'https://swapi.dev/api/people/';

  private ClientInterface $httpClient;
  private SWApiCache $cache;

  public function __construct(ClientInterface $httpClient, SWApiCache $cache)
  {
    $this->httpClient = $httpClient;
    $this->cache = $cache;
  }

  public function getData(): array
  {
    $peoples = $this->getAllPeoples();
    $homePlanets = $this->getHomePlanetsFromPeoples($peoples);
    return $this->combineData($peoples, $homePlanets);
  }

  protected function combineData(array $peoples, array $homePlanets): array
  {
    foreach ($peoples as $key => $people) {
      $peoples[$key]['homePlanet'] = $homePlanets[$people['homePlanetEndpoint']];
      unset($peoples[$key]['homePlanetEndpoint']);
    }
    return $peoples;
  }

  protected function getHomePlanetsFromPeoples(array $peoples): array
  {
    $homePlanetEndpoints = array_unique(
      array_column($peoples, 'homePlanetEndpoint')
    );
    $homePlanets = [];
    foreach ($homePlanetEndpoints as $homePlanetEndpoint) {
      $homePlanets[$homePlanetEndpoint] = $this->getHomePlanetNameByUrl($homePlanetEndpoint);
    }

    return $homePlanets;
  }

  protected function getHomePlanetNameByUrl(string $homePlanetEndpoint): string
  {
    $response = $this->makeRequest($homePlanetEndpoint);
    return $response['name'];
  }

  protected function getAllPeoples(): array
  {
    $peoples = [];
    $peopleEndpoint = self::API_ENDPOINT_PEOPLES;
    do {
      $getPeoples = $this->getPeoples($peopleEndpoint);
      $peoples = array_merge($peoples, $getPeoples['peoples']);
      $peopleEndpoint = $getPeoples['next'];
    } while ($getPeoples['next'] !== null);

    return $peoples;
  }

  protected function getPeoples(string $peopleEndpoint): array
  {
    $peoples = [];
    $response = $this->makeRequest($peopleEndpoint);
    foreach ($response['results'] as $people) {
      $peoples[] = [
        'name' => $people['name'],
        'gender' => $people['gender'],
        'homePlanetEndpoint' => $people['homeworld'],
      ];
    }
    return [
      'peoples' => $peoples,
      'next' => $response['next']
    ];
  }

  /**
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws SWApiException
   */
  protected function makeRequest($endpoint): array
  {
    $this->cache->checkLimit();
    $request = $this->httpClient->request('GET', $endpoint);
    $this->cache->increase();
    return $this->parseResponse($request);

  }

  protected function parseResponse(ResponseInterface $response): array
  {
    $this->validateResponse($response);
    $getContents = $response->getBody()->getContents();
    return json_decode($getContents, true);
  }

  /**
   * @throws SWApiException
   */
  protected function validateResponse(ResponseInterface $response): void
  {
    if ($response->getStatusCode() !== Response::HTTP_OK) {
      throw new SWApiException('Something goes wrong!');
    }
  }
}
