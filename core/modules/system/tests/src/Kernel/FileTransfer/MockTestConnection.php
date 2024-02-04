<?php

namespace Drupal\Tests\system\Kernel\FileTransfer;

/**
 * Mock connection object for FileTransferTest.
 */
class MockTestConnection {

  protected array $commandsRun = [];
  public string $connectionString;

  public function run($cmd): void {
    $this->commandsRun[] = $cmd;
  }

  public function flushCommands(): array {
    $out = $this->commandsRun;
    $this->commandsRun = [];
    return $out;
  }

}
