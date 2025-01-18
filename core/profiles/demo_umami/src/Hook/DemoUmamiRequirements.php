<?php

declare(strict_types=1);

namespace Drupal\demo_umami\Hook;

use Drupal\Core\Extension\ProfileExtensionList;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Requirements for the demo_umami module.
 */
class DemoUmamiRequirements {

  public function __construct(protected readonly ProfileExtensionList $profileExtenstionList) {}

  /**
   * Implements hook_runtime_requirements().
   */
  #[Hook('runtime_requirements')]
  public function runtime(): array {
    $requirements = [];
    $profile = \Drupal::installProfile();
    $info = $this->profileExtenstionList->getExtensionInfo($profile);
    $requirements['experimental_profile_used'] = [
      'title' => t('Experimental installation profile used'),
      'value' => $info['name'],
      'description' => t('Experimental profiles are provided for testing purposes only. Use at your own risk. To start building a new site, reinstall Drupal and choose a non-experimental profile.'),
      'severity' => REQUIREMENT_WARNING,
    ];
    return $requirements;
  }

}
