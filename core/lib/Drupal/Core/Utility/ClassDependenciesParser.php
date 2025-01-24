<?php

declare(strict_types=1);

namespace Drupal\Core\Utility;

use Drupal\Component\Utility\ClassDependenciesParser as ComponentClassDependenciesParser;
use Drupal\Core\Attribute\Dependencies;
use PhpParser\ConstExprEvaluationException;
use PhpParser\ConstExprEvaluator;
use PhpParser\Node\Expr\Array_;

/**
 * Extends component ClassDependenciesParser.
 *
 * In addition to interfaces, classes, and traits, this parser also accounts for
 * module dependencies declared in the Drupal\Core\Attribute\Dependencies
 * attribute.
 */
class ClassDependenciesParser extends ComponentClassDependenciesParser {

  /**
   * {@inheritdoc}
   */
  public function getClassDependencies(): array {
    $dependencies = parent::getClassDependencies();
    if (!$this->parsedClass) {
      return $dependencies;
    }

    // Filter out class, interface, and trait dependencies if their provider
    // is 'core', 'component', or matches the provider of the class.
    $classProvider = $this->getProviderFromNamespace((string) $this->parsedClass->namespacedName);
    foreach ($dependencies as $type => $typedDependencies) {
      $filtered = array_filter($typedDependencies, function ($dependency) use ($classProvider) {
        $dependencyProvider = $this->getProviderFromNamespace($dependency);
        return ($dependencyProvider !== $classProvider) && !in_array($dependencyProvider, ['core', 'component']);
      });
      $dependencies[$type] = $filtered;
    }

    // Include modules identified in the Dependencies attribute as dependencies.
    $modules = [];
    foreach ($this->getClassAttributes() as $classAttribute) {
      if (((string) $classAttribute->name === Dependencies::class) &&
          !empty($classAttribute->args)) {
        // Dependencies attribute has only one argument.
        $arg = reset($classAttribute->args);
        if ($arg->value instanceof Array_) {
          try {
            $modules = (new ConstExprEvaluator())->evaluateSilently($arg->value);
          }
          catch (ConstExprEvaluationException) {
          }
          break;
        }
      }
    }

    return $dependencies + ['module' => $modules];
  }

  /**
   * {@inheritdoc}
   */
  public static function hasMissingDependencies(array $dependencies): bool {
    // Check module dependencies first, to prevent errors from testing whether
    // interfaces, classes, or traits exist.
    $modules = $dependencies['module'] ?? [];
    if ($modules && !empty(array_diff($modules, array_keys(\Drupal::moduleHandler()->getModuleList())))) {
      return TRUE;
    }

    return parent::hasMissingDependencies($dependencies);
  }

  /**
   * Extracts the provider name from a Drupal namespace.
   *
   * @param string $namespace
   *   The namespace to extract the provider from.
   *
   * @return string|null
   *   The matching provider name, or NULL otherwise.
   */
  protected function getProviderFromNamespace(string $namespace): ?string {
    preg_match('|^Drupal\\\\(?<provider>[\w]+)\\\\|', $namespace, $matches);

    if (isset($matches['provider'])) {
      return mb_strtolower($matches['provider']);
    }

    return NULL;
  }

}
