<?php

declare(strict_types=1);

namespace Drupal\mysqli\Driver\Database\mysqli;

use Drupal\Core\Database\ConnectionNotDefinedException;
use Drupal\Core\Database\DatabaseAccessDeniedException;
use Drupal\Core\Database\DatabaseNotFoundException;
use Drupal\Core\Database\Transaction\TransactionManagerInterface;
use Drupal\mysql\Driver\Database\mysql\Connection as BaseMySqlConnection;

/**
 * MySQLi implementation of \Drupal\Core\Database\Connection.
 */
class Connection extends BaseMySqlConnection {

  /**
   * {@inheritdoc}
   */
  protected $statementWrapperClass = Statement::class;

  /**
   * Stores the server version after it has been retrieved from the database.
   */
  private string $serverVersion;

  public function __construct(
    protected $connection,
    protected $connectionOptions = [],
  ) {
    assert($this->connection instanceof \mysqli);
    assert(is_array($this->connectionOptions));
    // If the SQL mode doesn't include 'ANSI_QUOTES' (explicitly or via a
    // combination mode), then MySQL doesn't interpret a double quote as an
    // identifier quote, in which case use the non-ANSI-standard backtick.
    //
    // Because we still support MySQL 5.7, check for the deprecated combination
    // modes as well.
    //
    // @see https://dev.mysql.com/doc/refman/5.7/en/sql-mode.html#sqlmode_ansi_quotes
    $ansi_quotes_modes = ['ANSI_QUOTES', 'ANSI', 'DB2', 'MAXDB', 'MSSQL', 'ORACLE', 'POSTGRESQL'];
    $is_ansi_quotes_mode = FALSE;
    if (isset($this->connectionOptions['init_commands']['sql_mode'])) {
      foreach ($ansi_quotes_modes as $mode) {
        // None of the modes in $ansi_quotes_modes are substrings of other modes
        // that are not in $ansi_quotes_modes, so a simple stripos() does not
        // return false positives.
        if (stripos($this->connectionOptions['init_commands']['sql_mode'], $mode) !== FALSE) {
          $is_ansi_quotes_mode = TRUE;
          break;
        }
      }
    }
    if ($this->identifierQuotes === ['"', '"'] && !$is_ansi_quotes_mode) {
      $this->identifierQuotes = ['`', '`'];
    }

    // Manage the table prefix.
    $this->connectionOptions['prefix'] ??= '';
    $this->setPrefix($this->connectionOptions['prefix']);

    // Work out the database driver namespace if none is provided. This is
    // normally written to setting.php by installer or set by
    // \Drupal\Core\Database\Database::parseConnectionInfo().
    $this->connectionOptions['namespace'] ??= (new \ReflectionObject($this))->getNamespaceName();
  }

  /**
   * {@inheritdoc}
   */
  public static function open(array &$connection_options = []) {
    // Sets mysqli error reporting mode to report errors from mysqli function
    // calls and to throw mysqli_sql_exception for errors.
    // @see https://www.php.net/manual/en/mysqli-driver.report-mode.php
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    if (isset($connection_options['_dsn_utf8_fallback']) && $connection_options['_dsn_utf8_fallback'] === TRUE) {
      // Only used during the installer version check, as a fallback from utf8mb4.
      $charset = 'utf8';
    }
    else {
      $charset = 'utf8mb4';
    }

    // Allow PDO options to be overridden.
    $connection_options += [
      'pdo' => [],
    ];

    try {
      $mysqli = @new \mysqli(
        $connection_options['host'],
        $connection_options['username'],
        $connection_options['password'],
        $connection_options['database'] ?? '',
        !empty($connection_options['port']) ? (int) $connection_options['port'] : 3306,
        $connection_options['unix_socket'] ?? ''
      );
      if (!$mysqli->set_charset($charset)) {
        throw new InvalidCharsetException('Invalid charset ' . $charset);
      }
    }
    catch (\mysqli_sql_exception $e) {
      if ($e->getCode() === static::DATABASE_NOT_FOUND) {
        throw new DatabaseNotFoundException($e->getMessage(), $e->getCode(), $e);
      }
      elseif ($e->getCode() === static::ACCESS_DENIED) {
        throw new DatabaseAccessDeniedException($e->getMessage(), $e->getCode(), $e);
      }
      else {
        throw new ConnectionNotDefinedException('Invalid database connection: ' . $e->getMessage(), $e->getCode(), $e);
      }
      throw $e;
    }

    // Force MySQL to use the UTF-8 character set. Also set the collation, if a
    // certain one has been set; otherwise, MySQL defaults to
    // 'utf8mb4_general_ci' (MySQL 5) or 'utf8mb4_0900_ai_ci' (MySQL 8) for
    // utf8mb4.
    if (!empty($connection_options['collation'])) {
      $mysqli->query('SET NAMES ' . $charset . ' COLLATE ' . $connection_options['collation']);
    }
    else {
      $mysqli->query('SET NAMES ' . $charset);
    }

    // Set MySQL init_commands if not already defined.  Default Drupal's MySQL
    // behavior to conform more closely to SQL standards.  This allows Drupal
    // to run almost seamlessly on many different kinds of database systems.
    // These settings force MySQL to behave the same as postgresql, or sqlite
    // in regards to syntax interpretation and invalid data handling.  See
    // https://www.drupal.org/node/344575 for further discussion. Also, as MySQL
    // 5.5 changed the meaning of TRADITIONAL we need to spell out the modes one
    // by one.
    $connection_options += [
      'init_commands' => [],
    ];

    $connection_options['init_commands'] += [
      'sql_mode' => "SET sql_mode = 'ANSI,TRADITIONAL'",
    ];
    if (!empty($connection_options['isolation_level'])) {
      $connection_options['init_commands'] += [
        'isolation_level' => 'SET SESSION TRANSACTION ISOLATION LEVEL ' . strtoupper($connection_options['isolation_level']),
      ];
    }

    // Execute initial commands.
    foreach ($connection_options['init_commands'] as $sql) {
      $mysqli->query($sql);
    }

    return $mysqli;
  }

  /**
   * {@inheritdoc}
   */
  public function driver() {
    return 'mysqli';
  }

  /**
   * {@inheritdoc}
   */
  public function clientVersion() {
    return \mysqli_get_client_info();
  }

  /**
   * {@inheritdoc}
   */
  public function quote($string, $parameter_type = \PDO::PARAM_STR) {
    return "'" . $this->connection->escape_string((string) $string) . "'";
  }

  /**
   * {@inheritdoc}
   */
  public function lastInsertId(?string $name = NULL): string {
    return (string) $this->connection->insert_id;
  }

  /**
   * {@inheritdoc}
   */
  public function exceptionHandler() {
    return new ExceptionHandler();
  }

  /**
   * {@inheritdoc}
   */
  protected function driverTransactionManager(): TransactionManagerInterface {
    return new TransactionManager($this);
  }

}
