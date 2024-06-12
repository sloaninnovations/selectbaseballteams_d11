<?php

declare(strict_types=1);

namespace Drupal\TestTools\Extension\Dump;

use PHPUnit\Event\TestRunner\Finished as TestRunnerFinished;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

/**
 * Drupal's extension for printing dump() output results.
 *
 * @internal
 */
final class DebugDump implements Extension {

  /**
   * The path to the dump staging file.
   */
  private static string $stagingFilePath;

  /**
   * Whether colors should be used for printing.
   */
  private static bool $colors = FALSE;

  /**
   * {@inheritdoc}
   */
  public function bootstrap(
    Configuration $configuration,
    Facade $facade,
    ParameterCollection $parameters,
  ): void {
    // Determine staging file path.
    self::$stagingFilePath = tempnam(sys_get_temp_dir(), 'dpd');

    // Determine color output.
    $colors = $parameters->has('colors') ? $parameters->get('colors') : FALSE;
    self::$colors = filter_var($colors, \FILTER_VALIDATE_BOOLEAN);

    // Set the environment variable with the configuration.
    $config = json_encode([
      'stagingFilePath' => self::$stagingFilePath,
      'colors' => self::$colors,
    ]);
    putenv('DRUPAL_PHPUNIT_DUMPER_CONFIG=' . $config);

    $facade->registerSubscriber(new TestRunnerFinishedSubscriber($this));
  }

  /**
   * A CLI handler for \Symfony\Component\VarDumper\VarDumper.
   */
  public static function cliHandler($var) {
    $envConfig = getenv('DRUPAL_PHPUNIT_DUMPER_CONFIG');
    if ($envConfig === FALSE) {
      return;
    }
    $config = (array) json_decode($envConfig);

    $caller = self::getCaller();

    $cloner = new VarCloner();
    $dumper = new CliDumper();
    $dumper->setColors($config['colors']);
    $dump = [];
    $dumper->dump(
      $cloner->cloneVar($var),
      function ($line, $depth, $indent_pad) use (&$dump) {
        // A negative depth means "end of dump".
        if ($depth >= 0) {
          // Adds a two spaces indentation to the line.
          $dump[] = str_repeat($indent_pad, $depth) . $line;
        }
      }
    );

    file_put_contents(
      $config['stagingFilePath'],
      self::encodeDump($caller['test']->id(), $caller['file'], $caller['line'], $dump) . "\n",
      FILE_APPEND,
    );
  }

  private static function encodeDump(string $testId, ?string $file, ?int $line, array $dump): string {
    $data = [
      'test' => $testId,
      'file' => $file,
      'line' => $line,
      'dump' => $dump,
    ];
    $jsonData = json_encode($data);
    return base64_encode($jsonData);
  }

  private static function decodeDump(string $encodedData): array {
    $jsonData = base64_decode($encodedData);
    return (array) json_decode($jsonData);
  }

  private static function getCaller(): array {
    $backtrace = debug_backtrace();
    while (!isset($backtrace[0]['function']) || $backtrace[0]['function'] !== 'dump') {
      array_shift($backtrace);
    }
    $call['file'] = $backtrace[1]['file'];
    $call['line'] = $backtrace[1]['line'];

    $call['test'] = $backtrace[2]['object']->valueObjectForEvents();

    return $call;
  }

  /**
   * Prints the dumps generated during the test.
   */
  public function testRunnerFinished(TestRunnerFinished $event): void {
    $contents = file_get_contents(self::$stagingFilePath);

    // Cleanup.
    unlink(self::$stagingFilePath);
    putenv('DRUPAL_PHPUNIT_DUMPER_CONFIG');

    if (empty($contents)) {
      return;
    }

    print "\n\n";

    print "dump() output\n";
    print "-------------\n\n";

    $encodedDumps = explode("\n", $contents);
    array_pop($encodedDumps);

    $dumps = [];
    foreach ($encodedDumps as $encodedDump) {
      $dump = self::decodeDump($encodedDump);
      $test = $dump['test'];
      unset($dump['test']);
      $dumps[$test][] = $dump;
    }
    foreach ($dumps as $testId => $testDumps) {
      print $testId . "\n";
      foreach ($testDumps as $dump) {
        print "in " . $dump['file'] . ", line " . $dump['line'] . ":\n";
        foreach ($dump['dump'] as $line) {
          print $line . "\n";
        }
        print "\n";
      }
      print "\n";
    }

  }

}
