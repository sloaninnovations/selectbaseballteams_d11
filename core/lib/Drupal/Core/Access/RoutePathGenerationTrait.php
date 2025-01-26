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
   *     'parameter1' => 'value1',
   *   ]
   *   This will transform a route path like '/route/path/{parameter1}{parameter2}'
   *   into '/route/path/value1'.
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

  /**
   * Generates a "legacy" route path by replacing parameter placeholders with their values.
   *
   * This method uses the legacy behavior of replacing placeholders in the given route path
   * but does not remove placeholders without corresponding values in the array. This allows
   * checking for legacy CSRF tokens and treat them as valid too.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route object containing the path with placeholders.
   * @param array $parameters
   *   An associative array of parameters to replace in the route path.
   *   Example:
   *   [
   *     'parameter1' => 'value1',
   *   ]
   *   This will transform a route path like '/route/path/{parameter1}/{parameter2}'
   *   into '/route/path/value1/{parameter2}'.
   *
   * @return string
   *   The generated path with all placeholders replaced by their corresponding
   *   values if they exist in the $parameters array.
   */
  public function generateLegacyRoutePath(Route $route, array $parameters): string {
    $path = ltrim($route->getPath(), '/');
    // Replace the path parameters with values from the parameters array.
    foreach ($parameters as $param => $value) {
      $path = str_replace("{{$param}}", $value, $path);
    }

    return $path;
  }

}
