<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * @group user
 * @coversDefaultClass \Drupal\user\Entity\User
 */
class UserRoleTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user'];

  /**
   * @covers ::isAdmin
   */
  public function testUserIsAdmin(): void {
    $role1 = Role::create(['id' => 'role1']);
    $role2 = Role::create(['id' => 'role2']);
    /** @var \Drupal\user\UserInterface $account */
    $account = User::create([
      'name' => $this->randomMachineName(),
      'roles' => [$role1, $role2],
    ]);
    $this->assertFalse($account->isAdmin());

    // Add an admin role.
    $role3 = Role::create(['id' => 'role3', 'is_admin' => TRUE]);
    $account->get('roles')->appendItem($role3);
    $this->assertTrue($account->isAdmin());

    // Remove the admin role.
    $account->set('roles', [$role1, $role2]);
    $this->assertFalse($account->isAdmin());
  }

}
