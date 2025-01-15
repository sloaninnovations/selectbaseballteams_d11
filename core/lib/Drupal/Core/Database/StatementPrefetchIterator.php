<?php

namespace Drupal\Core\Database;

use Drupal\Core\Database\Event\StatementExecutionEndEvent;
use Drupal\Core\Database\Event\StatementExecutionFailureEvent;
use Drupal\Core\Database\Event\StatementExecutionStartEvent;
use Drupal\Core\Database\Statement\FetchAs;
use Drupal\Core\Database\Statement\PdoTrait;
use Drupal\Core\Database\Statement\PrefetchedResult;
use Drupal\Core\Database\Statement\StatementBase;

/**
 * An implementation of StatementInterface that prefetches all data.
 *
 * This class behaves very similar to a StatementWrapperIterator of a
 * \PDOStatement but as it always fetches every row it is possible to
 * manipulate those results.
 */
class StatementPrefetchIterator extends StatementBase {

  use PdoTrait;

  /**
   * The client database Statement object.
   *
   * For a \PDO client connection, this will be a \PDOStatement object.
   */
  protected ?object $clientStatement;

  /**
   * Holds the default fetch style.
   *
   * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use
   * $defaultFetchMode instead.
   *
   * @see https://www.drupal.org/node/3488338
   */
  protected int $defaultFetchStyle = \PDO::FETCH_OBJ;

  /**
   * Constructs a StatementPrefetchIterator object.
   *
   * @param object $clientConnection
   *   Client database connection object, for example \PDO.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param string $queryString
   *   The query string.
   * @param array $driverOptions
   *   Driver-specific options.
   * @param bool $rowCountEnabled
   *   (optional) Enables counting the rows matched. Defaults to FALSE.
   */
  public function __construct(
    object $clientConnection,
    Connection $connection,
    string $queryString,
    protected array $driverOptions = [],
    bool $rowCountEnabled = FALSE,
  ) {
    parent::__construct($connection, $clientConnection, $queryString, $rowCountEnabled);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($args = [], $options = []) {
    if (isset($options['fetch']) && is_int($options['fetch'])) {
      @trigger_error("Passing the 'fetch' key as an integer to \$options in execute() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use a case of \Drupal\Core\Database\FetchAs enum instead. See https://www.drupal.org/node/3488338", E_USER_DEPRECATED);
    }

    if ($this->connection->isEventEnabled(StatementExecutionStartEvent::class)) {
      $startEvent = new StatementExecutionStartEvent(
        spl_object_id($this),
        $this->connection->getKey(),
        $this->connection->getTarget(),
        $this->getQueryString(),
        $args ?? [],
        $this->connection->findCallerFromDebugBacktrace()
      );
      $this->connection->dispatchEvent($startEvent);
    }

    // Prepare and execute the statement.
    try {
      $this->clientStatement = $this->getStatement($this->queryString, $args);
      $return = $this->clientExecute($args, $options);
    }
    catch (\Exception $e) {
      if (isset($startEvent) && $this->connection->isEventEnabled(StatementExecutionFailureEvent::class)) {
        $this->connection->dispatchEvent(new StatementExecutionFailureEvent(
          $startEvent->statementObjectId,
          $startEvent->key,
          $startEvent->target,
          $startEvent->queryString,
          $startEvent->args,
          $startEvent->caller,
          $startEvent->time,
          get_class($e),
          $e->getCode(),
          $e->getMessage(),
        ));
      }
      unset($this->clientStatement);
      throw $e;
    }

    // Fetch all the data from the reply, in order to release any lock as soon
    // as possible.
    $data = $this->clientFetchAll(FetchAs::Associative);
    $this->result = new PrefetchedResult(
      $this->fetchMode,
      $this->fetchOptions,
      $data,
      $this->rowCountEnabled ? $this->clientRowCount() : NULL,
    );

    // Destroy the statement as soon as possible. See the documentation of
    // \Drupal\sqlite\Driver\Database\sqlite\Statement for an explanation.
    unset($this->clientStatement);
    $this->markResultsetIterable($return);

    if (isset($options['fetch'])) {
      if (is_string($options['fetch'])) {
        // Default to an object. Note: db fields will be added to the object
        // before the constructor is run. If you need to assign fields after
        // the constructor is run. See https://www.drupal.org/node/315092.
        $this->setFetchMode(FetchAs::ClassObject, $options['fetch']);
      }
      else {
        $this->setFetchMode($options['fetch']);
      }
    }

    if (isset($startEvent) && $this->connection->isEventEnabled(StatementExecutionEndEvent::class)) {
      $this->connection->dispatchEvent(new StatementExecutionEndEvent(
        $startEvent->statementObjectId,
        $startEvent->key,
        $startEvent->target,
        $startEvent->queryString,
        $startEvent->args,
        $startEvent->caller,
        $startEvent->time
      ));
    }

    return $return;
  }

  /**
   * Grab a PDOStatement object from a given query and its arguments.
   *
   * Some drivers (including SQLite) will need to perform some preparation
   * themselves to get the statement right.
   *
   * @param string $query
   *   The query.
   * @param array|null $args
   *   An array of arguments. This can be NULL.
   *
   * @return object
   *   A PDOStatement object.
   */
  protected function getStatement(string $query, ?array &$args = []): object {
    return $this->connection->prepare($query, $this->driverOptions);
  }

  /**
   * {@inheritdoc}
   */
  public function setFetchMode($mode, $a1 = NULL, $a2 = []) {
    if (is_int($mode)) {
      @trigger_error("Passing the \$mode argument as an integer to setFetchMode() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use a case of \Drupal\Core\Database\FetchAs enum instead. See https://www.drupal.org/node/3488338", E_USER_DEPRECATED);
      $mode = $this->pdoToFetchAs($mode);
    }
    // @todo Remove backwards compatibility statement below in drupal:12.0.0.
    // @phpstan-ignore property.deprecated
    $this->defaultFetchStyle = $this->fetchAsToPdo($mode);
    return parent::setFetchMode($mode, $a1, $a2);
  }

  /**
   * {@inheritdoc}
   */
  public function fetch($fetch_style = NULL, $cursor_orientation = \PDO::FETCH_ORI_NEXT, $cursor_offset = NULL) {
    if (is_int($fetch_style)) {
      @trigger_error("Passing the \$fetch_style argument as an integer to fetch() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use a case of \Drupal\Core\Database\FetchAs enum instead. See https://www.drupal.org/node/3488338", E_USER_DEPRECATED);
      $fetch_style = $this->pdoToFetchAs($fetch_style);
    }

    // \PDOStatement is picky about the number of arguments in some cases so we
    // need to pass the exact number of arguments we were given.
    $row = match(func_num_args()) {
      0 => parent::fetch(),
      1 => parent::fetch($fetch_style),
      2 => parent::fetch($fetch_style, $cursor_orientation),
      default => parent::fetch($fetch_style, $cursor_orientation, $cursor_offset),
    };
    return $row;
  }

  /**
   * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use
   *   ::fetchField() instead.
   *
   * @see https://www.drupal.org/node/3490312
   */
  public function fetchColumn($index = 0) {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use ::fetchField() instead. See https://www.drupal.org/node/3490312', E_USER_DEPRECATED);
    return $this->fetchField($index);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAll($mode = NULL, $column_index = NULL, $constructor_arguments = NULL) {
    if (is_int($mode)) {
      @trigger_error("Passing the \$mode argument as an integer to fetchAll() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use a case of \Drupal\Core\Database\FetchAs enum instead. See https://www.drupal.org/node/3488338", E_USER_DEPRECATED);
      $mode = $this->pdoToFetchAs($mode);
    }
    return parent::fetchAll($mode, $column_index, $constructor_arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAllAssoc($key, $fetch = NULL) {
    if (is_int($fetch)) {
      @trigger_error("Passing the \$fetch argument as an integer to fetchAllAssoc() is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use a case of \Drupal\Core\Database\FetchAs enum instead. See https://www.drupal.org/node/3488338", E_USER_DEPRECATED);
      $fetch = $this->pdoToFetchAs($fetch);
    }
    return parent::fetchAllAssoc($key, $fetch);
  }

}
