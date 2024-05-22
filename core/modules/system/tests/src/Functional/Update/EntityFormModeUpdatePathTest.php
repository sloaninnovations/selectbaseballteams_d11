<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Update;

use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests update path for the entity form mode description value from '' to NULL.
 *
 * @group contact
 */
class EntityFormModeUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-10.3.0.filled.standard.php.gz',
      __DIR__ . '/../../../../../system/tests/fixtures/update/remove-description-from-user-register-form-mode.php',
    ];
  }

  /**
   * Tests update path for the entity form mode description value from '' to NULL.
   */
  public function testRunUpdates(): void {
    $form_mode_type = EntityFormMode::load('user.register');
    $this->assertInstanceOf(EntityFormMode::class, $form_mode_type);
    $this->assertSame("\n", $form_mode_type->get('description'));
    $this->runUpdates();

    $form_mode_type = EntityFormMode::load('user.register');
    $this->assertInstanceOf(EntityFormMode::class, $form_mode_type);

    $this->assertNull($form_mode_type->get('description'));
    $this->assertSame('', $form_mode_type->getDescription());
  }

}
