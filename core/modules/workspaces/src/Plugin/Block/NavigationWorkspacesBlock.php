<?php

declare(strict_types=1);

namespace Drupal\workspaces\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\workspaces\WorkspaceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a workspaces navigation block class.
 *
 * @internal
 */
#[Block(
  id: 'navigation_workspaces',
  admin_label: new TranslatableMarkup('Navigation Workspaces'),
)]
final class NavigationWorkspacesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected WorkspaceManagerInterface $workspaceManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('workspaces.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account): AccessResultInterface {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer workspaces',
      'view own workspace',
      'view any workspace',
    ], 'OR');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $active_workspace = $this->workspaceManager->getActiveWorkspace();
    return [
      'workspaces' => [
        '#lazy_builder' => ['workspaces.lazy_builders:renderNavigationLinks', []],
        '#create_placeholder' => TRUE,
        '#lazy_builder_preview' => [
          '#type' => 'component',
          '#component' => 'navigation:toolbar-button',
          '#props' => [
            'html_tag' => 'a',
            'text' => $this->t('Workspace'),
          ],
        ],
        '#cache' => [
          'contexts' => ['user.permissions'],
        ],
      ],
    ];
  }

}
