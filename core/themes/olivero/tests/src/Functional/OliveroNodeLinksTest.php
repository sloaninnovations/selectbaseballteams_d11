<?php

declare(strict_types=1);

namespace Drupal\Tests\olivero\Functional;

use Drupal\Tests\node\Functional\NodeLinksTest;

/**
 * Tests the output of node links (read more, add new comment, etc).
 *
 * @group olivero
 */
class OliveroNodeLinksTest extends NodeLinksTest {

  /**
   * Run the same tests as the node module, but use the Olivero theme.
   *
   * {@inheritdoc}
   */
  protected $defaultTheme = 'olivero';

}
