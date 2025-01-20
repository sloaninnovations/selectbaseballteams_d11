<?php

declare(strict_types=1);

namespace Drupal\navigation\Plugin\TopBarItem;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
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

  /**
   * Constructs a PageActions object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\navigation\NavigationRenderer $navigationRenderer
   *   The navigation renderer.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected NavigationRenderer $navigationRenderer,
    protected RouteMatchInterface $routeMatch,
  ) {
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
      $container->get(NavigationRenderer::class),
      $container->get(RouteMatchInterface::class),
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
    if (!$this->navigationRenderer->hasLocalTasks()) {
      return $build;
    }

    $local_tasks = $this->navigationRenderer->getLocalTasks();
    $featured_local_task = $this->getFeaturedLocalTask($local_tasks);
    if (isset($featured_local_task)) {
      unset($local_tasks['tasks'][$featured_local_task['route']]);
    }

    $build += [
      '#theme' => 'top_bar_local_tasks',
      '#local_tasks' => $local_tasks['tasks'],
      '#featured_local_task' => $featured_local_task,
    ];

    assert($local_tasks['cacheability'] instanceof CacheableMetadata);
    $local_tasks['cacheability']->applyTo($build);

    return $build;
  }

  /**
   * Gets the featured local task.
   *
   * @param array $local_tasks
   *   The array of local tasks for the current page.
   *
   * @return array|null
   *   The featured local task definition if available. NULL otherwise.
   */
  protected function getFeaturedLocalTask(array $local_tasks): ?array {
    $featured_local_task = NULL;
    $current_route_name = $this->routeMatch->getRouteName();
    $canonical_pattern = '/^entity\.(.+?)\.canonical$/';
    if (preg_match($canonical_pattern, $current_route_name, $matches)) {
      $entity_type = $matches[1];
      $edit_route = "entity.$entity_type.edit_form";
      // For core entities, the local task name matches the route name. If
      // needed, we could iterate over the items and check the actual route.
      if (isset($local_tasks['tasks'][$edit_route]) && $local_tasks['tasks'][$edit_route]['#access']?->isAllowed()) {
        $featured_local_task = [
          'route' => $edit_route,
          'task' => $local_tasks['tasks'][$edit_route],
          'icon' => 'edit',
        ];
      }
    }
    return $featured_local_task;
  }

}
