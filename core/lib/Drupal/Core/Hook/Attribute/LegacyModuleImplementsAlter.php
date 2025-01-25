<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

/**
 * Defines a LegacyModuleImplementsAlter attribute object.
 *
 * This allows contrib and core to maintain legacy hook_module_implements_alter
 * alongside the new attribute-based ordering. This means that a contrib module
 * can simultaneously support Drupal 11.2 and older versions of Drupal.
 *
 * Marking hook_module_implements_alter as #LegacyModuleImplementsAlter will
 * prevent hook_module_implements_alter from running when attribute-based
 * ordering is available.
 *
 * On older versions of Drupal which are not aware of attribute-based ordering,
 * only the legacy hook implementation is executed.
 *
 * For more information, see https://www.drupal.org/node/3496788.
 */
#[\Attribute(\Attribute::TARGET_FUNCTION)]
class LegacyModuleImplementsAlter {

}
