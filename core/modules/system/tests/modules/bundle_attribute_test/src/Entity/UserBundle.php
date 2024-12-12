<?php

namespace Drupal\bundle_attribute_test\Entity;

use Drupal\Core\Entity\Attribute\Bundle;
use Drupal\user\Entity\User;

/**
 * Test bundle with no bundle.
 */
#[Bundle(
  entityType: 'user',
)]
class UserBundle extends User {}
