<?php

declare(strict_types=1);

namespace Drupal\block_test\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a block to test XSS in title.
 */
#[Block(
  id: "test_xss_title",
  admin_label: new TranslatableMarkup("<script>alert('XSS subject');</script>"),
  category: new TranslatableMarkup("<script>alert('XSS category');</script>"),
)]
class TestXSSTitleBlock extends TestCacheBlock {
}
