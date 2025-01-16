<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Breadcrumb\Breadcrumb
 * @group Breadcrumb
 */
class BreadcrumbTest extends UnitTestCase {

  /**
   * @covers ::setLinks
   */
  public function testSetLinks(): void {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->setLinks([new Link('Home', Url::fromRoute('<front>'))]);
    $links = $breadcrumb->getLinks();
    $this->assertCount(1, $links);
  }

}
