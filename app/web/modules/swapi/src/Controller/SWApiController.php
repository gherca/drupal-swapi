<?php

namespace Drupal\swapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\swapi\Service\SWApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SWApiController extends ControllerBase
{
  private SWApiService $apiService;

  public function __construct(SWApiService $apiService)
  {
    $this->apiService = $apiService;
  }

  public static function create(ContainerInterface $container): SWApiController
  {
    return new static(
      $container->get('swapi.service')
    );
  }

  public function content(): array
  {
    $peoples = $this->apiService->getData();
    return [
      '#theme' => 'swapi',
      '#peoples' => $peoples,
    ];
  }
}
