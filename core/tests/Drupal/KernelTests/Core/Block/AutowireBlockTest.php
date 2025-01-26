<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Block;

use Drupal\autowire_test\Plugin\Block\AutowireBlock;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DependencyInjection\Exception\AutowiringFailedException;

/**
 * Tests that blocks can be autowired.
 *
 * @group block
 */
class AutowireBlockTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'block', 'autowire_test'];

  /**
   * Tests that autowire errors are handled correctly.
   */
  public function testAutowireBlock(): void {
    $block = \Drupal::service('plugin.manager.block')->createInstance('autowire');
    $this->assertInstanceOf(AutowireBlock::class, $block);
  }

  /**
   * Tests that autowire errors are handled correctly.
   */
  public function testAutowireError(): void {
    $this->expectException(AutowiringFailedException::class);
    $this->expectExceptionMessage('Cannot autowire service "Drupal\Core\Lock\LockBackendInterface": argument "$lock" of method "Drupal\autowire_test\Plugin\Block\AutowireErrorBlock::_construct()", you should configure its value explicitly.');

    \Drupal::service('plugin.manager.block')->createInstance('autowire_error');
  }

}
