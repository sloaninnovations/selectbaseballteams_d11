<?php

namespace Drupal\bundle_attribute_test\Entity;

use Drupal\Core\Entity\Attribute\Bundle;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_test\Entity\EntityTest;

/**
 * Test bundle with overridden label.
 */
#[Bundle(
  entityType: 'entity_test',
  bundle: 'test_bundle_with_label',
  label: new TranslatableMarkup('Overridden label'),
)]
class TestBundleWithLabel extends EntityTest {}
