<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

/**
 * Defines a ProceduralHookScanStop attribute object.
 *
 * This allows contrib and core to mark when a file has no more
 * procedural hooks.
 */
#[\Attribute(\Attribute::TARGET_FUNCTION)]
class ProceduralHookScanStop {

}
