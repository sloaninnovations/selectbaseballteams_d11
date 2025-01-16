<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Utility;

use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

// cSpell:ignore changeme

/**
 * Test outbound path processor.
 */
final class TestOutboundPathProcessor implements OutboundPathProcessorInterface {

  #[\Override]
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    if ($path === '/external-is-local-changeme') {
      return '/changed';
    }
    return $path;
  }

}
