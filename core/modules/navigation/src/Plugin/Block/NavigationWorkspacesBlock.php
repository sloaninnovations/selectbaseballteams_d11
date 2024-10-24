<?php

declare(strict_types=1);

namespace Drupal\navigation\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a workspaces navigation block class.
 *
 * @internal
 *
 * @todo Move to Workspaces module as part of the core MR process.
 */
#[Block(
  id: 'navigation_workspaces',
  admin_label: new TranslatableMarkup('Navigation Workspaces'),
)]
final class NavigationWorkspacesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler.
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->moduleHandler = $container->get('module_handler');
    return $instance;
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
    // This navigation block requires the Workspaces module. Once the plugin is
    // moved to that module, this should not be necessary.
    if (!$this->moduleHandler->moduleExists('workspaces')) {
      return [];
    }

    return [
      'workspaces' => [
        // We specifically test an invalid callback here. We need to let
        // PHPStan ignore it.
        // @phpstan-ignore-next-line
        '#lazy_builder' => ['navigation.workspaces_lazy_builders:renderNavigationLinks', []],
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
