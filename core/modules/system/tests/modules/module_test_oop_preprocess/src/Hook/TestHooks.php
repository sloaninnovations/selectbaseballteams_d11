<?php

declare(strict_types=1);

namespace Drupal\module_test_oop_preprocess\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Attribute\Preprocess;
use Drupal\Core\Hook\Attribute\TemplatePreprocess;

/**
 * Hook implementations for module_test_oop_preprocess.
 */
class TestHooks {

  #[Hook('preprocess')]
  public function rootPreprocess($arg): mixed {
    return $arg;
  }

  #[Preprocess('test')]
  public function preprocessTest($arg): mixed {
    return $arg;
  }

  #[TemplatePreprocess('test')]
  public function templatePreprocessTest($arg): mixed {
    return $arg;
  }

}
