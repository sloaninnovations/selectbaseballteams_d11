<?php

namespace Drupal\Core\Access;

use Symfony\Component\Routing\Route;

trait RoutePathGenerationTrait {

  public function generateRoutePath(Route $route, array $parameters): string {
    $path = ltrim($route->getPath(), '/');

    // Replace the path parameters with values from the parameters array.
    foreach ($parameters as $param => $value) {
      $path = str_replace("{{$param}}", $value, $path);
    }

    // Remove not replaced params.
    $path = preg_replace('/\/{[^}]+}/', '', $path);

    // Remove trailing slash.
    $path = rtrim($path, '/');

    return $path;
  }

}
