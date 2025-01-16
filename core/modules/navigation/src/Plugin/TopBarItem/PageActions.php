<?php

declare(strict_types=1);

namespace Drupal\navigation\Plugin\TopBarItem;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\navigation\Attribute\TopBarItem;
use Drupal\navigation\NavigationRenderer;
use Drupal\navigation\TopBarItemBase;
use Drupal\navigation\TopBarRegion;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Page Actions basic top bar item.
 */
#[TopBarItem(
  id: 'page_actions',
  region: TopBarRegion::Actions,
  label: new TranslatableMarkup('Page Actions'),
)]
final class PageActions extends TopBarItemBase implements ContainerFactoryPluginInterface {

  public function __construct(array $configuration, $plugin_id, $plugin_definition, private NavigationRenderer $navigationRenderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get(NavigationRenderer::class)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build = [
      '#cache' => [
        'contexts' => ['route'],
      ],
    ];

    // Local tasks for content entities.
    if ($this->navigationRenderer->hasLocalTasks()) {
      $local_tasks = $this->navigationRenderer->getLocalTasks();
      $current_route_name = \Drupal::routeMatch()->getRouteName();

      // Canonical routes for supported entity types.
      $canonical_routes = [
        'entity.node.canonical',
        'entity.media.canonical',
        'entity.taxonomy_term.canonical',
        'entity.user.canonical',
      ];

      $exposed_local_tasks = [];

      // Check if the current route is canonical.
      if (in_array($current_route_name, $canonical_routes, TRUE)) {
        // Look for the "Edit" task in the local tasks.
        $edit_route_name = key(array_filter(
          $local_tasks['tasks'],
          fn($task) => isset($task['#link']['#title']) && $task['#link']['#title'] === 'Edit'
        ));

        if (isset($edit_route_name) && array_key_exists($edit_route_name, $local_tasks['tasks'])) {
          $exposed_local_tasks[] = [
            'task' => $local_tasks['tasks'][$edit_route_name],
            'icon' => 'edit',
          ];
          unset($local_tasks['tasks'][$edit_route_name]);
        }
      }

      $build += [
        '#theme' => 'top_bar_local_tasks',
        '#local_tasks' => $local_tasks['tasks'],
        '#exposed_local_tasks' => $exposed_local_tasks,
      ];

      assert($local_tasks['cacheability'] instanceof CacheableMetadata);
      $local_tasks['cacheability']->applyTo($build);
    }

    return $build;
  }

}
