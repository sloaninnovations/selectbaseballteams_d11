<?php

namespace Drupal\path_alias;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Modifies services in the container.
 */
class PathAliasServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container->getDefinition('path.matcher')
      ->setClass(AliasPathMatcher::class)
      ->addArgument(new Reference('path_alias.manager'));
  }

}
