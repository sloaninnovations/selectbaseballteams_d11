<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Kernel;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Test description.
 *
 * @coversDefaultClass \Drupal\workspaces\WorkspacesEntityRepository
 *
 * @group workspaces
 */
final class WorkspacesParallelTest extends KernelTestBase {

  private EntityTypeManagerInterface $entityTypeManager;
  private EntityRepositoryInterface $entityRepository;

  use UserCreationTrait;
  use NodeCreationTrait;
  use WorkspaceTestTrait;
  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'system',
    'workspaces',
    'field',
    'filter',
    'node',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->initializeWorkspacesModule();
    $this->installConfig(['filter', 'node', 'system']);
    $this->createContentType(['type' => 'page']);

    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->entityRepository = \Drupal::service('entity.repository');

    $this->config('workspaces.settings')->set('parallel', TRUE)->save();
  }

  /**
   * Test that the live workspace only pulls in a non-workspace revision during
   * editing when loaded with EntityRepository::getActive().
   */
  public function testParallelEditing(): void {
    $node = $this->createNode([
      'title' => 'Change in the live site',
      'type' => 'page',
    ]);

    $stage_ws = \Drupal::entityTypeManager()
      ->getStorage('workspace')
      ->load('stage');

    $ws_manager = \Drupal::service('workspaces.manager');
    $ws_manager->setActiveWorkspace($stage_ws);

    $node->title = 'Change in stage site';
    $node->validate();
    $node->save();

    $ws_manager->switchToLive();

    $nR = $this->entityRepository->getActive('node', $node->id());

    // We should see the live site change, not the stage one, even though that
    // is more recent.
    self::assertEquals('Change in the live site', $nR->label());
  }

}
