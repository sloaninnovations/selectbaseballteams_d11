<?php

declare(strict_types=1);

namespace Drupal\navigation\Plugin\TopBarItem;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\navigation\Attribute\TopBarItem;
use Drupal\navigation\TopBarItemBase;
use Drupal\navigation\TopBarRegion;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides the Page Context top bar item.
 */
#[TopBarItem(
  id: 'page_context',
  region: TopBarRegion::Context,
  label: new TranslatableMarkup('Page Context'),
)]
class PageContext extends TopBarItemBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Constructs a new PageContext instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private EntityTypeManagerInterface $entityTypeManager,
    private RouteMatchInterface $routeMatch,
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
    $container->get('entity_type.manager'),
    $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build = [];

    foreach ($this->routeMatch->getParameters() as $parameter) {
      if ($parameter instanceof EntityInterface) {
        $title = $parameter->label();
        $status = '';
        $status_class = '';
        if ($parameter instanceof EntityPublishedInterface) {
          $status = $parameter->isPublished()
          ? $this->t('Published')
          : $this->t('Unpublished');
          $status_class = $parameter->isPublished() ? 'published' : 'unpublished';
        }
        $items = [
        [
          '#markup' => $title,
          '#wrapper_attributes' => ['class' => ['context-title']],
        ],
        [
          '#markup' => $status,
          '#wrapper_attributes' => ['class' => ['context-status', $status_class]],
        ],
        ];
        $build = [
          '#theme' => 'item_list',
          '#items' => $items,
          '#attributes' => [
            'class' => ['navigation-top-bar-context'],
          ],
        ];
      }
    }
    return $build;
  }

}
