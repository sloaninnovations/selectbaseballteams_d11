<?php

namespace Drupal\path_alias;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\PathMatcher;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

/**
 * Extends the default path matcher to check aliases.
 */
class AliasPathMatcher extends PathMatcher {

  /**
   * The path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Creates a new PathMatcher.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   The alias manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RouteMatchInterface $route_match, AliasManagerInterface $alias_manager) {
    parent::__construct($config_factory, $route_match);
    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFrontPage() {
    if (parent::checkFrontPage()) {
      return TRUE;
    }

    // Ensure that the code can also be executed when there is no active
    // route match, like on exception responses.
    if (!$this->routeMatch->getRouteName()) {
      return FALSE;
    }

    if ($this->routeMatch->getRouteName()) {
      $url = Url::fromRouteMatch($this->routeMatch);
      $path = '/' . $url->getInternalPath();
      return $this->aliasManager->getAliasByPath($path) === $this->getFrontPagePath();
    }

    return FALSE;
  }

}
