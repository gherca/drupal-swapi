<?php

namespace Drupal\swapi\Service;

use Drupal\Core\Cache\CacheBackendInterface;

class SWApiCache
{
  const RATE_LIMIT_KEY = 'swapi_rate_limit00122';
  const TIME_TO_EXPIRE = 86400;
  const REACH_LIMIT_API = 10000;

  private CacheBackendInterface $cache;

  public function __construct(CacheBackendInterface $cache)
  {
    $this->cache = $cache;
  }

  public function get()
  {
    return $this->cache->get(self::RATE_LIMIT_KEY);
  }

  /**
   * @throws SWApiException
   */
  public function checkLimit(): void
  {
    if ($cached = $this->get()) {
      if ((int)$cached->data >= self::REACH_LIMIT_API) {
        throw new SWApiException('You have reach the API limit for today! ' . self::REACH_LIMIT_API);
      }
    }
  }

  public function increase()
  {
    $timeToExpire = time() + self::TIME_TO_EXPIRE;
    $data = 1;
    if ($cached = $this->get()) {
      $timeToExpire = $cached->expire;
      $data = $cached->data + 1;
    }

    $this->cache->set(
      self::RATE_LIMIT_KEY,
      $data,
      $timeToExpire
    );
  }
}
