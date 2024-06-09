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
use Symfony\Component\VarDumper\VarDumper;

/**
 * Drupal's extension for printing dump() output results.
 *
 * @internal
 */
final class DebugDump implements Extension {

  /**
   * Whether colors should be used for printing.
   */
  private static bool $colors = FALSE;

  /**
   * The accumulated results of dump() calls.
   */
  private static array $dumps = [];

  /**
   * {@inheritdoc}
   */
  public function bootstrap(
    Configuration $configuration,
    Facade $facade,
    ParameterCollection $parameters,
  ): void {
    // Determine color output.
    $colors = $parameters->has('colors') ? $parameters->get('colors') : FALSE;
    self::$colors = filter_var($colors, \FILTER_VALIDATE_BOOLEAN);

    VarDumper::setHandler(self::class . '::cliHandler');

    $facade->registerSubscriber(new TestRunnerFinishedSubscriber($this));
  }

  /**
   * A CLI handler for \Symfony\Component\VarDumper\VarDumper.
   */
  public static function cliHandler($var) {
    $caller = self::getCaller();
    $cloner = new VarCloner();
    $dumper = new CliDumper();
    $dumper->setColors(self::$colors);
    $dumper->dump(
      $cloner->cloneVar($var),
      function ($line, $depth, $indent_pad) use ($caller) {
        // A negative depth means "end of dump".
        if ($depth >= 0) {
          // Adds a two spaces indentation to the line.
          self::$dumps[$caller['test']->id()][$caller['file']][$caller['line']][] = str_repeat($indent_pad, $depth) . $line;
        }
      }
    );
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
    if (empty(self::$dumps)) {
      return;
    }

    print "\n\n";
    print "dump() output\n";
    print "-------------\n\n";
    foreach (self::$dumps as $testId => $testDumps) {
      print $testId . "\n";
      foreach ($testDumps as $fileName => $fileDumps) {
        foreach ($fileDumps as $line => $dump) {
          print "in " . $fileName . ", line " . $line . ":\n";
          foreach ($dump as $line) {
            print $line . "\n";
          }
        }
        print "\n";
      }
    }
    self::$dumps = [];
  }

}
