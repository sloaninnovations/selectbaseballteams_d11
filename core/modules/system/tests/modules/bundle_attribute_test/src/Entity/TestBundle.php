<?php

namespace Drupal\bundle_attribute_test\Entity;

use Drupal\Core\Entity\Attribute\Bundle;
use Drupal\entity_test\Entity\EntityTest;

/**
 * Test bundle.
 */
#[Bundle(
  entityType: 'entity_test',
  bundle: 'test_bundle',
)]
class TestBundle extends EntityTest {}
