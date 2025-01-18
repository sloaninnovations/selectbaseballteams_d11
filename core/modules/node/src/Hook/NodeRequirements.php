<?php

declare(strict_types=1);

namespace Drupal\node\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Core\Url;

/**
 * Requirements for the node module.
 */
class NodeRequirements {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ModuleHandlerInterface $moduleHandler,
    protected readonly TranslationManager $translationManager,
  ) {}

  /**
   * Implements hook_runtime_requirements().
   */
  #[Hook('runtime_requirements')]
  public function runtime(): array {
    $requirements = [];
    // Only show rebuild button if there are either 0, or 2 or more, rows
    // in the {node_access} table, or if there are modules that
    // implement hook_node_grants().
    $grant_count = $this->entityTypeManager->getAccessControlHandler('node')->countGrants();
    if ($grant_count != 1 || $this->moduleHandler->hasImplementations('node_grants')) {
      $value = $this->translationManager->formatPlural($grant_count, 'One permission in use', '@count permissions in use', ['@count' => $grant_count]);
    }
    else {
      $value = t('Disabled');
    }

    $requirements['node_access'] = [
      'title' => t('Node Access Permissions'),
      'value' => $value,
      'description' => t('If the site is experiencing problems with permissions to content, you may have to rebuild the permissions cache. Rebuilding will remove all privileges to content and replace them with permissions based on the current modules and settings. Rebuilding may take some time if there is a lot of content or complex permission settings. After rebuilding has completed, content will automatically use the new permissions. <a href=":rebuild">Rebuild permissions</a>', [
        ':rebuild' => Url::fromRoute('node.configure_rebuild_confirm')->toString(),
      ]),
    ];
    return $requirements;
  }

}
