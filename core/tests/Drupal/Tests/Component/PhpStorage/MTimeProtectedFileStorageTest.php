<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\PhpStorage;

use Drupal\Component\PhpStorage\MTimeProtectedFileStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the MTimeProtectedFileStorage implementation.
 *
 * @group Drupal
 * @group PhpStorage
 */
#[CoversClass(MTimeProtectedFileStorage::class)]
#[Group('Drupal')]
#[Group('PhpStorage')]
class MTimeProtectedFileStorageTest extends MTimeProtectedFileStorageBase {

  /**
   * The expected test results for the security test.
   *
   * The default implementation protects against even the filemtime change so
   * both iterations will return FALSE.
   */
  protected array $expected = [FALSE, FALSE];

  /**
   * The PHP storage class to test.
   */
  protected $storageClass = 'Drupal\Component\PhpStorage\MTimeProtectedFileStorage';

}
