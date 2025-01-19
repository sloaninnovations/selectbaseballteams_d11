<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Recipe;

use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\Role;

/**
 * @covers \Drupal\Core\Config\Action\Plugin\ConfigAction\EntityClone
 * @group Recipe
 */
class EntityCloneConfigActionTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig('user');

    $this->createRole(['access user profiles'], 'test');
  }

  public function testErrorIfOriginalDoesNotExist(): void {
    $this->expectException(ConfigActionException::class);
    $this->expectExceptionMessage("Cannot clone 'user.role.nope' because it does not exist.");
    $this->container->get('plugin.manager.config_action')
      ->applyAction('cloneAs', 'user.role.nope', 'user.role.yep');
  }

  public function testSuccessfulClone(): void {
    $this->container->get('plugin.manager.config_action')
      ->applyAction('cloneAs', 'user.role.test', 'cloned');

    $clone = Role::load('cloned');
    $this->assertInstanceOf(Role::class, $clone);
    $this->assertTrue($clone->hasPermission('access user profiles'));
  }

  public function testNoConflictWithExistingClone(): void {
    $this->createRole(['administer site configuration'], 'cloned');

    $this->container->get('plugin.manager.config_action')
      ->applyAction('cloneAs', 'user.role.test', 'cloned');

    $clone = Role::load('cloned');
    $this->assertInstanceOf(Role::class, $clone);
    $this->assertTrue($clone->hasPermission('administer site configuration'));
    $this->assertFalse($clone->hasPermission('access user profiles'));
  }

}
