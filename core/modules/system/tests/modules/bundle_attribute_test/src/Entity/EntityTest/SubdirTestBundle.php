<?php

namespace Drupal\bundle_attribute_test\Entity\EntityTest;

use Drupal\Core\Entity\Attribute\Bundle;
use Drupal\entity_test\Entity\EntityTest;

/**
 * Subdir test bundle.
 */
#[Bundle(
  entityType: 'entity_test',
  bundle: 'subdir_test_bundle',
)]
class SubdirTestBundle extends EntityTest {}
