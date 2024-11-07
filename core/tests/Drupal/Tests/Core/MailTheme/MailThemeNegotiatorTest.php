<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\MailTheme;

use Drupal\Core\MailTheme\MailThemeNegotiator;
use Drupal\Core\MailTheme\MailThemeNegotiatorInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\MailTheme\MailThemeNegotiator
 * @group MailTheme
 */
class MailThemeNegotiatorTest extends UnitTestCase {

  /**
   * Tests that no result is returned if there is no negotiator.
   *
   * @covers ::applies
   * @covers ::determineMailTheme
   */
  public function testNoThemeNegotiators(): void {
    $negotiator = new MailThemeNegotiator([]);
    $this->assertTrue($negotiator->applies('test'));
    $this->assertNull($negotiator->determineMailTheme('test'));
  }

  /**
   * Tests that the result is returned after the first negotiator applied.
   *
   * @covers ::determineMailTheme
   */
  public function testFirstOfTwoThemeNegotiators(): void {
    $negotiator1 = $this->createMock(MailThemeNegotiatorInterface::class);
    $negotiator1->expects($this->once())
      ->method('applies')
      ->with('update_status_notify')
      ->willReturn(TRUE);
    $negotiator1->expects($this->once())
      ->method('determineMailTheme')
      ->with('update_status_notify')
      ->willReturn('stark');

    $negotiator2 = $this->createMock(MailThemeNegotiatorInterface::class);
    $negotiator2->expects($this->never())
      ->method('applies');
    $negotiator2->expects($this->never())
      ->method('determineMailTheme');

    $negotiator = new MailThemeNegotiator([$negotiator1, $negotiator2]);
    $result = $negotiator->determineMailTheme('update_status_notify');
    $this->assertSame('stark', $result);
  }

  /**
   * Tests that second result is returned if first negotiator doesn't apply.
   *
   * @covers ::determineMailTheme
   */
  public function testSecondOfTwoThemeNegotiators(): void {
    $negotiator1 = $this->createMock(MailThemeNegotiatorInterface::class);
    $negotiator1->expects($this->once())
      ->method('applies')
      ->with('update_status_notify')
      ->willReturn(FALSE);
    $negotiator1->expects($this->never())
      ->method('determineMailTheme');

    $negotiator2 = $this->createMock(MailThemeNegotiatorInterface::class);
    $negotiator2->expects($this->once())
      ->method('applies')
      ->with('update_status_notify')
      ->willReturn(TRUE);
    $negotiator2->expects($this->once())
      ->method('determineMailTheme')
      ->with('update_status_notify')
      ->willReturn('olivero');

    $negotiator = new MailThemeNegotiator([$negotiator1, $negotiator2]);
    $result = $negotiator->determineMailTheme('update_status_notify');
    $this->assertSame('olivero', $result);
  }

}
