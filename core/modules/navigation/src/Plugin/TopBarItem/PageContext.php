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
        $items[] = [
          '#markup' => $parameter->label(),
          '#wrapper_attributes' => ['class' => ['context-title']],
        ];
        try {
          $items[] = [
            '#markup' => $this->getStatus($parameter),
            '#wrapper_attributes' => [
              'class' => ['context-status', $this->getStatusClass($parameter)],
            ],
          ];
        }
        catch (\InvalidArgumentException) {
          // No status to show for the given entity.
        }
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

  /**
   * Retrieves the published status of the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which the status is being retrieved.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The translated status.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the entity does not implement EntityPublishedInterface.
   *   Child classes may override this method to provide more complete coverage.
   */
  protected function getStatus(EntityInterface $entity): TranslatableMarkup {
    if (!$entity instanceof EntityPublishedInterface) {
      throw new \InvalidArgumentException('Only EntityPublishedInterface are supported by navigation module.');
    }
    return $entity->isPublished() ? $this->t('Published') : $this->t('Unpublished');
  }

  /**
   * Determines the CSS class to represent the status of an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity whose status class is to be determined.
   *
   * @return string
   *   The CSS class representing the status of the entity.
   *
   * @throws \InvalidArgumentException
   *   If the provided entity does not implement EntityPublishedInterface.
   *   Child classes may override this method to provide more complete coverage.
   */
  protected function getStatusClass(EntityInterface $entity): string {
    if (!$entity instanceof EntityPublishedInterface) {
      throw new \InvalidArgumentException('Only EntityPublishedInterface are supported by navigation module.');
    }
    return $entity->isPublished() ? 'published' : 'unpublished';
  }

}
