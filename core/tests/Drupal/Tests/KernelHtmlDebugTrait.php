<?php

declare(strict_types=1);

namespace Drupal\Tests;

use Drupal\Component\Utility\Html;

/**
 * Provides the debug functions for kernel tests.
 *
 * @todo Merge this and BrowserHtmlDebugTrait once their methods are aligned.
 */
trait KernelHtmlDebugTrait {

  /**
   * Class name for HTML output logging.
   *
   * @var string
   */
  protected $htmlOutputClassName;

  /**
   * Directory name for HTML output logging.
   *
   * @var string
   */
  protected $htmlOutputDirectory;

  /**
   * Counter storage for HTML output logging.
   *
   * @var string
   */
  protected $htmlOutputCounterStorage;

  /**
   * Counter for HTML output logging.
   *
   * @var int
   */
  protected $htmlOutputCounter = 1;

  /**
   * HTML output enabled.
   *
   * @var bool
   */
  protected $htmlOutputEnabled = FALSE;

  /**
   * The file name to write the list of URLs to.
   *
   * This file is read by the PHPUnit result printer.
   *
   * @var string
   *
   * @see \Drupal\Tests\Listeners\HtmlOutputPrinter
   */
  protected $htmlOutputFile;

  /**
   * HTML output test ID.
   *
   * @var int
   */
  protected $htmlOutputTestId;

  /**
   * The Base URI to use for links to the output files.
   *
   * @var string
   */
  protected $htmlOutputBaseUrl;

  /**
   * Formats HTTP headers as string for HTML output logging.
   *
   * @param array[] $headers
   *   Headers that should be formatted.
   *
   * @return string
   *   The formatted HTML string.
   */
  protected function formatHtmlOutputHeaders(array $headers) {
    $flattened_headers = array_map(function ($header) {
      if (is_array($header)) {
        return implode(';', array_map('trim', $header));
      }
      else {
        return $header;
      }
    }, $headers);
    return '<hr />Headers: <pre>' . Html::escape(var_export($flattened_headers, TRUE)) . '</pre>';
  }

  /**
   * Logs a HTML output message in a text file.
   *
   * The link to the HTML output message will be printed by the results printer.
   *
   * @param string $message
   *   The HTML output message to be stored.
   *
   * @see \Drupal\Tests\Listeners\VerbosePrinter::printResult()
   */
  protected function htmlOutput(string $message) {
    if (!$this->htmlOutputEnabled) {
      return;
    }
    $message = '<hr />ID #' . $this->htmlOutputCounter . ' (<a href="' . $this->htmlOutputClassName . '-' . ($this->htmlOutputCounter - 1) . '-' . $this->htmlOutputTestId . '.html">Previous</a> | <a href="' . $this->htmlOutputClassName . '-' . ($this->htmlOutputCounter + 1) . '-' . $this->htmlOutputTestId . '.html">Next</a>)<hr />' . $message;
    $html_output_filename = $this->htmlOutputClassName . '-' . $this->htmlOutputCounter . '-' . $this->htmlOutputTestId . '.html';
    file_put_contents($this->htmlOutputDirectory . '/' . $html_output_filename, $message);
    file_put_contents($this->htmlOutputCounterStorage, $this->htmlOutputCounter++);
    // Do not use the file_url_generator service as the module_handler service
    // might not be available.
    $uri = $this->htmlOutputBaseUrl . '/sites/simpletest/browser_output/' . $html_output_filename;
    file_put_contents($this->htmlOutputFile, $uri . "\n", FILE_APPEND);
  }

  /**
   * Creates the directory to store browser output.
   *
   * Creates the directory to store browser output in if a file to write
   * URLs to has been created by \Drupal\Tests\Listeners\HtmlOutputPrinter.
   */
  protected function initBrowserOutputFile() {
    global $base_url;
    $browser_output_file = getenv('BROWSERTEST_OUTPUT_FILE');
    $this->htmlOutputEnabled = is_file($browser_output_file);
    $this->htmlOutputBaseUrl = getenv('BROWSERTEST_OUTPUT_BASE_URL') ?: $base_url;
    if ($this->htmlOutputEnabled) {
      $this->htmlOutputFile = $browser_output_file;
      $this->htmlOutputClassName = str_replace("\\", "_", static::class);
      $this->htmlOutputDirectory = DRUPAL_ROOT . '/sites/simpletest/browser_output';
      // Do not use the file_system service so this method can be called before
      // it is available. Checks !is_dir() twice around mkdir() because a
      // concurrent test might have made the directory and caused mkdir() to
      // fail. In this case we can still use the directory even though we failed
      // to make it.
      if (!is_dir($this->htmlOutputDirectory) && !@mkdir($this->htmlOutputDirectory, 0775, TRUE) && !is_dir($this->htmlOutputDirectory)) {
        throw new \RuntimeException(sprintf('Unable to create directory: %s', $this->htmlOutputDirectory));
      }
      if (!file_exists($this->htmlOutputDirectory . '/.htaccess')) {
        file_put_contents($this->htmlOutputDirectory . '/.htaccess', "<IfModule mod_expires.c>\nExpiresActive Off\n</IfModule>\n");
      }
      $this->htmlOutputCounterStorage = $this->htmlOutputDirectory . '/' . $this->htmlOutputClassName . '.counter';
      $pieces = explode('/', $this->siteDirectory);
      $this->htmlOutputTestId = end($pieces);
      if (is_file($this->htmlOutputCounterStorage)) {
        $this->htmlOutputCounter = max(1, (int) file_get_contents($this->htmlOutputCounterStorage)) + 1;
      }
    }
  }

}
