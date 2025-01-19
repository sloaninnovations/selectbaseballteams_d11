<?php

namespace Drupal\Core\Session;

/**
 * Access policies that do not require caching can implement this interface.
 *
 * If all active access policies implement this interface,
 * \Drupal\Core\Session\AccessPolicyProcessor will skip the persistent cache.
 */
interface AccessPolicyCacheOptionalInterface {

}
