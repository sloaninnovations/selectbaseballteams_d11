<?php

declare(strict_types=1);

namespace Drupal\Tests\Core;

use Drupal\Core\Result;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests the Result type.
 */
#[CoversClass(Result::class)]
class ResultTest extends UnitTestCase {

  /**
   * Tests the `\Drupal\Core\Result` type.
   */
  public function testResultType(): void {

    $this->assertTrue(Result::ok("Foo")->isOk());

    $this->assertTrue(Result::ok("Foo")->isOk());

    $this->assertFalse(Result::ok("Foo")->isError());

    $this->assertEquals(Result::ok(5)->getValue(), 5);

    $this->assertFalse(Result::error("Foo")->isOk());

    $this->assertTrue(Result::error("Foo")->isError());

    $complexObject = new \stdClass();
    $this->assertEquals(Result::error($complexObject)->getValue(), $complexObject);
  }

}
