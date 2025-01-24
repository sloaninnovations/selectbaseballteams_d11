<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Database;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Event\DatabaseEvent;
use Drupal\Core\Database\Event\StatementEvent;
use Drupal\Core\Database\Event\StatementExecutionEndEvent;
use Drupal\Core\Database\Event\StatementExecutionFailureEvent;
use Drupal\Core\Database\Event\StatementExecutionStartEvent;
use Drupal\Core\EventDispatcher\EventDispatcherFactory;
use Drupal\Core\EventDispatcher\EventDispatcherFactoryInterface;
use Drupal\Core\EventDispatcher\EventDispatcherFactoryStage;
use Drupal\Tests\Core\Database\Stub\StubConnection;
use Drupal\Tests\Core\Database\Stub\StubPDO;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the database events.
 *
 * We need to run these tests in isolation since they instantiate the event
 * dispatcher via the factory that uses a static to hold it.
 *
 * @coversDefaultClass \Drupal\Core\Database\Connection
 * @group Database
 * @runTestsInSeparateProcesses
 */
class DatabaseEventsTest extends UnitTestCase {

  /**
   * A database connection.
   */
  protected Connection $connection;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->connection = new StubConnection($this->createMock(StubPDO::class), [], ['', ''], new EventDispatcherFactory());
  }

  /**
   * @covers ::isEventEnabled
   * @covers ::enableEvents
   * @covers ::disableEvents
   */
  public function testEventEnablingAndDisabling(): void {
    $this->connection->enableEvents(StatementEvent::all());
    $this->assertTrue($this->connection->isEventEnabled(StatementExecutionStartEvent::class));
    $this->assertTrue($this->connection->isEventEnabled(StatementExecutionEndEvent::class));
    $this->assertTrue($this->connection->isEventEnabled(StatementExecutionFailureEvent::class));
    $this->connection->disableEvents([
      StatementExecutionEndEvent::class,
    ]);
    $this->assertTrue($this->connection->isEventEnabled(StatementExecutionStartEvent::class));
    $this->assertFalse($this->connection->isEventEnabled(StatementExecutionEndEvent::class));
    $this->assertTrue($this->connection->isEventEnabled(StatementExecutionFailureEvent::class));
    $this->connection->disableEvents(StatementEvent::all());
    $this->assertFalse($this->connection->isEventEnabled(StatementExecutionStartEvent::class));
    $this->assertFalse($this->connection->isEventEnabled(StatementExecutionEndEvent::class));
    $this->assertFalse($this->connection->isEventEnabled(StatementExecutionFailureEvent::class));
  }

  /**
   * @covers ::enableEvents
   */
  public function testEnableInvalidEvent(): void {
    $this->expectException(\AssertionError::class);
    $this->expectExceptionMessage('Event class foo does not exist');
    $this->connection->enableEvents(['foo']);
  }

  /**
   * @covers ::disableEvents
   */
  public function testDisableInvalidEvent(): void {
    $this->expectException(\AssertionError::class);
    $this->expectExceptionMessage('Event class bar does not exist');
    $this->connection->disableEvents(['bar']);
  }

  /**
   * @covers ::dispatchEvent
   */
  public function testEventDispatchingWhenNoContainerAvailable(): void {
    $this->connection->dispatchEvent($this->createMock(DatabaseEvent::class));
    $reflectedProperty = new \ReflectionProperty($this->connection, 'eventDispatcher');
    $eventDispatcher = $reflectedProperty->getValue($this->connection);
    $this->assertInstanceOf(EventDispatcherFactoryInterface::class, $eventDispatcher);
    $this->assertSame(EventDispatcherFactoryStage::PreBootstrap, $eventDispatcher->getInstanceStage());
  }

}
