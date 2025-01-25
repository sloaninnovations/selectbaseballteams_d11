<?php

declare(strict_types=1);

namespace Drupal\Core\Test;

use Drupal\Core\Test\Exception\MissingGroupException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\TextUI\Configuration\Builder;
use PHPUnit\TextUI\Configuration\TestSuiteBuilder;

/**
 * Discovers available tests using the PHPUnit API.
 *
 * @internal
 */
class PhpUnitTestDiscovery {

  /**
   * The map of legacy test suite identifiers to PHPUnit.xml ones.
   *
   * @var array<string,string>
   */
  private array $map = [
    'PHPUnit-FunctionalJavascript' => 'functional-javascript',
    'PHPUnit-Functional' => 'functional',
    'PHPUnit-Kernel' => 'kernel',
    'PHPUnit-Unit' => 'unit',
    'PHPUnit-Build' => 'build',
  ];

  /**
   * The reverse map of legacy test suite identifiers to PHPUnit.xml ones.
   *
   * @var array<string,string>
   */
  private array $reverseMap;

  /**
   * The warnings generated during the discovery.
   *
   * @var list<string>
   */
  private array $warnings = [];

  public function __construct(
    private string $root,
  ) {
    $this->reverseMap = array_flip($this->map);
  }

  /**
   * Discovers available tests.
   *
   * @param string|null $extension
   *   (optional) The name of an extension to limit discovery to; e.g., 'node'.
   * @param list<string> $types
   *   (optional) An array of included test types.
   * @param string|null $directory
   *   (optional) Limit discovered tests to a specific directory.
   *
   * @return array<class-string,array<'name'|'description'|'group'|'groups'|'type'|'file',string|array>>
   *   An array of tests keyed by the group name. If a test belongs to multiple
   *   groups, it will appear under all group keys it belongs to.
   *
   * @code
   *     $groups['block'] => [
   *       'Drupal\Tests\block\Functional\BlockTest' => [
   *         'name' => 'Drupal\Tests\block\Functional\BlockTest',
   *         'description' => 'Tests block UI CRUD functionality.',
   *         'group' => 'block',
   *         'groups' => ['block', 'group2', 'group3'],
   *         'type' => 'PHPUnit-Functional',
   *         'file' => '{root}/core/modules/block/tests/src/Functional/BlockTest.php',
   *       ],
   *     ];
   * @endcode
   */
  public function getTestClasses(?string $extension = NULL, array $types = [], ?string $directory = NULL): array {
    $this->warnings = [];

    $args = ['--configuration', $this->root . \DIRECTORY_SEPARATOR . 'core'];

    if (!empty($types)) {
      $tmp = [];
      foreach ($types as $i) {
        $tmp[] = $this->map[$i] ?? $i;
      }
      $args[] = '--testsuite=' . implode(',', $tmp);
    }

    if ($directory !== NULL) {
      $args[] = $directory;
    }

    $phpUnitConfiguration = (new Builder())->build($args);

    // TestSuiteBuilder calls the test data providers during the discovery.
    // Data providers may be changing the Drupal service container, which leads
    // to potential issues. We save the current container before running the
    // discovery, and in case a change is detected, reset it and raise
    // warnings so that developers can tune their data provider code.
    if (\Drupal::hasContainer()) {
      $container = \Drupal::getContainer();
      $containerObjectId = spl_object_id($container);
    }
    $phpUnitTestSuite = (new TestSuiteBuilder())->build($phpUnitConfiguration);
    if (isset($containerObjectId) && $containerObjectId !== spl_object_id(\Drupal::getContainer())) {
      $this->warnings[] = '*** The service container was changed during the test discovery ***';
      $this->warnings[] = 'Probably a test data provider method called \\Drupal::setContainer.';
      $this->warnings[] = 'Ensure that all the data providers restore the original container before returning data.';
      assert(isset($container));
      \Drupal::setContainer($container);
    }

    if ($directory !== NULL) {
      $list = [];
      if ($phpUnitTestSuite->isForTestClass()) {
        do {
          if ($extension !== NULL && !str_starts_with($phpUnitTestSuite->name(), "Drupal\\Tests\\{$extension}\\")) {
            continue;
          }

          // Take the test suite name from the class namespace.
          $testSuite = 'PHPUnit-' . TestDiscovery::getPhpunitTestSuite($phpUnitTestSuite->name());
          if (!empty($types) && !in_array($testSuite, $types, TRUE)) {
            continue;
          }

          $item = $this->getTestClassInfo($phpUnitTestSuite, $testSuite);

          foreach ($item['groups'] as $group) {
            $list[$group][$item['name']] = $item;
          }
        } while (FALSE);
      }
      else {
        foreach ($phpUnitTestSuite->tests() as $testClass) {
          if ($extension !== NULL && !str_starts_with($testClass->name(), "Drupal\\Tests\\{$extension}\\")) {
            continue;
          }

          // Take the test suite name from the class namespace.
          $testSuite = 'PHPUnit-' . TestDiscovery::getPhpunitTestSuite($testClass->name());
          if (!empty($types) && !in_array($testSuite, $types, TRUE)) {
            continue;
          }

          $item = $this->getTestClassInfo($testClass, $testSuite);

          foreach ($item['groups'] as $group) {
            $list[$group][$item['name']] = $item;
          }
        }
      }
    }
    else {
      $list = [];
      foreach ($phpUnitTestSuite->tests() as $testSuite) {
        foreach ($testSuite->tests() as $testClass) {
          if ($extension !== NULL && !str_starts_with($testClass->name(), "Drupal\\Tests\\{$extension}\\")) {
            continue;
          }

          // Take the test suite name from the parent test suite.
          $item = $this->getTestClassInfo(
            $testClass,
            $this->reverseMap[$testSuite->name()] ?? $testSuite->name(),
          );

          foreach ($item['groups'] as $group) {
            $list[$group][$item['name']] = $item;
          }
        }
      }
    }

    // Sort the groups and tests within the groups by name.
    uksort($list, 'strnatcasecmp');
    foreach ($list as &$tests) {
      uksort($tests, 'strnatcasecmp');
    }

    return $list;
  }

  /**
   * Discovers all class files in all available extensions.
   *
   * @param string|null $extension
   *   (optional) The name of an extension to limit discovery to; e.g., 'node'.
   * @param string|null $directory
   *   (optional) Limit discovered tests to a specific directory.
   *
   * @return array
   *   A classmap containing all discovered class files; i.e., a map of
   *   fully-qualified classnames to path names.
   */
  public function findAllClassFiles(?string $extension = NULL, ?string $directory = NULL): array {
    $testClasses = $this->getTestClasses($extension, [], $directory);
    $classMap = [];
    foreach ($testClasses as $group) {
      foreach ($group as $className => $info) {
        $classMap[$className] = $info['file'];
      }
    }
    return $classMap;
  }

  /**
   * Returns the warnings generated during the discovery.
   *
   * @return list<string>
   *   The warnings.
   */
  public function getWarnings(): array {
    return $this->warnings;
  }

  /**
   * Returns the test class information.
   *
   * @param \PHPUnit\Framework\Test $testClass
   *   The test class.
   * @param string $testSuite
   *   The test suite of this test class.
   *
   * @return array<'name'|'description'|'group'|'groups'|'type'|'file'|'tests_count',string|array>
   *   The test class information.
   */
  private function getTestClassInfo(Test $testClass, string $testSuite): array {
    $reflection = new \ReflectionClass($testClass->name());
    $docComment = $reflection->getDocComment();

    $annotations = [];
    // Look for annotations, allow an arbitrary amount of spaces before the
    // * but nothing else.
    preg_match_all('/^[ ]*\* (\@[^\s]*)(.*)/m', $docComment, $matches);
    if (isset($matches[1])) {
      foreach ($matches[1] as $key => $annotation) {
        $annotations[$annotation][] = substr($matches[2][$key], 1);
      }
    }

    // Let PHPUnit API return the groups, as it will deal transparently
    // with annotations or attributes, but skip groups generated by
    // PHPUnit internally and starting with a double underscore prefix.
    $groups = array_filter($testClass->groups(), function (string $value): bool {
      return !str_starts_with($value, '__');
    });
    if (empty($groups)) {
      throw new MissingGroupException(sprintf('Missing group metadata in test class %s', $testClass->name()));
    }

    $coversClassAttributes = $reflection->getAttributes(CoversClass::class, \ReflectionAttribute::IS_INSTANCEOF);
    if (isset($coversClassAttributes[0])) {
      $description = sprintf('Tests %s.', $coversClassAttributes[0]->getArguments()[0]);
    }
    elseif (isset($annotations['@coversDefaultClass'][0])) {
      $description = sprintf('Tests %s.', $annotations['@coversDefaultClass'][0]);
    }
    else {
      $description = TestDiscovery::parseTestClassSummary($docComment);
    }

    // Find the test cases count.
    $count = 0;
    foreach ($testClass->tests() as $testCase) {
      if ($testCase instanceof TestCase) {
        // If it's a straight test method, counts 1.
        $count++;
      }
      else {
        // It's a data provider test suite, count 1 per data set provided.
        $count += count($testCase->tests());
      }
    }

    return [
      'name' => $testClass->name(),
      'group' => $groups[0],
      'groups' => $groups,
      'type' => $testSuite,
      'description' => $description,
      'file' => $reflection->getFileName(),
      'tests_count' => $count,
    ];
  }

}
