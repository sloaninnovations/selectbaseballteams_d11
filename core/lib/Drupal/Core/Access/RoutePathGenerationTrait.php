<?php

namespace Drupal\Core\Access;

use Symfony\Component\Routing\Route;

/**
 * Provides a method for generating route paths, replacing parameter placeholders with their values.
 */
trait RoutePathGenerationTrait {

  /**
   * Generates a route path by replacing parameter placeholders with their values.
   *
   * This method replaces placeholders in the given route path using the provided
   * array of parameters. Placeholders without corresponding values in the array
   * are removed from the resulting path.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route object containing the path with placeholders.
   * @param array $parameters
   *   An associative array of parameters to replace in the route path.
   *   Example:
   *   [
   *     'parameter-placeholder' => 'parameter-value',
   *   ]
   *   This will transform a route path like '/route/path/{parameter-placeholder}'
   *   into '/route/path/parameter-value'.
   *
   * @return string
   *   The generated path with all placeholders replaced by their corresponding
   *   values or removed if no matching parameter exists.
   */
  public function generateRoutePath(Route $route, array $parameters): string {
    $path = ltrim($route->getPath(), '/');

    // Replace path parameters with their corresponding values from the parameters array.
    foreach ($parameters as $param => $value) {
      if (NULL !== $value) {
        $path = str_replace("{{$param}}", $value, $path);
      }
    }

    // Remove placeholders that were not replaced.
    $path = preg_replace('/\/{[^}]+}/', '', $path);

    // Remove trailing slashes (multiple slashes may result from the removal of unreplaced placeholders).
    $path = rtrim($path, '/');

    return $path;
  }

}
