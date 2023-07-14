<?php

namespace Drupal\views;

use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Plugin\views\filter\BooleanOperator;
use Drupal\views\Plugin\ViewsHandlerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a BC layer for modules providing old configurations.
 *
 * @internal
 */
class ViewsConfigUpdater implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * The views data service.
   *
   * @var \Drupal\views\ViewsData
   */
  protected $viewsData;

  /**
   * An array of helper data for the multivalue base field update.
   *
   * @var array
   */
  protected $multivalueBaseFieldsUpdateTableInfo;

  /**
   * The Views filter plugin manager service.
   *
   * @var \Drupal\views\Plugin\ViewsHandlerManager
   */
  protected $filterPluginManager;

  /**
   * Flag determining whether deprecations should be triggered.
   *
   * @var bool
   */
  protected $deprecationsEnabled = TRUE;

  /**
   * Stores which deprecations were triggered.
   *
   * @var bool
   */
  protected $triggeredDeprecations = [];

  /**
   * ViewsConfigUpdater constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed config manager.
   * @param \Drupal\views\ViewsData $views_data
   *   The views data service.
   * @param \Drupal\views\Plugin\ViewsHandlerManager $filter_plugin_manager
   *   The Views filter plugin manager service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    TypedConfigManagerInterface $typed_config_manager,
    ViewsData $views_data,
    ViewsHandlerManager $filter_plugin_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->typedConfigManager = $typed_config_manager;
    $this->viewsData = $views_data;
    $this->filterPluginManager = $filter_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('config.typed'),
      $container->get('views.views_data'),
      $container->get('plugin.manager.views.filter')
    );
  }

  /**
   * Sets the deprecations enabling status.
   *
   * @param bool $enabled
   *   Whether deprecations should be enabled.
   */
  public function setDeprecationsEnabled($enabled) {
    $this->deprecationsEnabled = $enabled;
  }

  /**
   * Processes all display handlers.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The View to update.
   * @param bool $return_on_changed
   *   Whether processing should stop after a change is detected.
   * @param callable $handler_processor
   *   A callback performing the actual update.
   *
   * @return bool
   *   Whether the view was updated.
   */
  protected function processDisplayHandlers(ViewEntityInterface $view, $return_on_changed, callable $handler_processor) {
    $changed = FALSE;
    $displays = $view->get('display');
    $handler_types = ['field', 'argument', 'sort', 'relationship', 'filter'];

    foreach ($displays as $display_id => &$display) {
      foreach ($handler_types as $handler_type) {
        $handler_type_plural = $handler_type . 's';
        if (!empty($display['display_options'][$handler_type_plural])) {
          foreach ($display['display_options'][$handler_type_plural] as $key => &$handler) {
            if ($handler_processor($handler, $handler_type, $key, $display_id)) {
              $changed = TRUE;
              if ($return_on_changed) {
                return $changed;
              }
            }
          }
        }
      }
    }

    if ($changed) {
      $view->set('display', $displays);
    }

    return $changed;
  }

  /**
   * Update boolean filter settings.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   The View to update.
   *
   * @return bool
   *   Whether the view was updated.
   */
  public function needsBooleanFilterAcceptNullUpdate(ViewEntityInterface $view): bool {
    return $this->processDisplayHandlers($view, TRUE, function (array &$handler, string $handler_type) use ($view) {
      return $this->processBooleanFilterAcceptNull($handler, $handler_type, $view);
    });
  }

  /**
   * Processes the boolean filter settings to accept null.
   *
   * @param array $handler
   *   A display handler.
   * @param string $handler_type
   *   The handler type.
   * @param \Drupal\views\ViewEntityInterface $view
   *   The View to update.
   *
   * @return bool
   *   Whether the handler was updated.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the filter plugin instance cannot be created.
   */
  protected function processBooleanFilterAcceptNull(array &$handler, string $handler_type, ViewEntityInterface $view): bool {
    $changed = FALSE;
    $displays = $view->get('display');
    foreach ($displays as &$display) {
      if (isset($display['display_options']['filters'])) {
        foreach ($display['display_options']['filters'] as &$filter) {
          // Any of the children of the modified classes will also be inheriting
          // the new settings.
          $filter_instance = $this->filterPluginManager->getHandler($filter);
          if (($filter_instance instanceof BooleanOperator) && !isset($filter['accept_null'])) {
            $filter['accept_null'] = FALSE;
            $changed = TRUE;
          }
        }
      }
    }
    if ($changed) {
      $view->set('display', $displays);
    }
    return $changed;
  }

}
