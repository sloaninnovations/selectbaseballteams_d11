<?php

declare(strict_types=1);
namespace Drupal\Tests\system\Kernel\FileTransfer;

/**
 * Mock connection object for FileTransferTest.
 */
class MockTestConnection {

  /**
   * The commands that have been run.
   *
   * @var array
   */
  protected array $commandsRun = [];

  /**
   * The connection string.
   *
   * @var string
   */
  public string $connectionString;

  /**
   * Runs a command.
   *
   * @param mixed $cmd
   *   The command to run.
   */
  public function run($cmd): void {
    $this->commandsRun[] = $cmd;
  }

  /**
   * Flushes the commands that have been run.
   *
   * @return array
   *   The flushed commands.
   */
  public function flushCommands(): array {
    $out = $this->commandsRun;
    $this->commandsRun = [];
    return $out;
  }

}
