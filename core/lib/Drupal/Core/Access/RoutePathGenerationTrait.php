<?php

namespace Drupal\Core\Access;

use Symfony\Component\Routing\Route;

/**
 * Provides a method for generating route paths, replacing parameter placeholders with their values.
 */
trait RoutePathGenerationTrait {

  /**
   * Performs parameter placeholder replacements on a route path, given an array of parameters.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route.
   * @param array $parameters
   *   The parameters to substitute on the route path.
   *
   * @return string
   */
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
