<?php

declare(strict_types=1);

namespace Drupal\Tests;

use Behat\Mink\Driver\BrowserKitDriver;
use Behat\Mink\Mink;
use Behat\Mink\Selector\SelectorsHandler;
use Behat\Mink\Session;
use Drupal\TestTools\HttpKernel\KernelTestHttpKernelBrowser;

/**
 * Provides UI helper methods using the HTTP kernel to make requests.
 *
 * This can be used by Kernel tests.
 */
trait HttpKernelUiHelperTrait {

  use BrowserHtmlDebugTrait;

  /**
   * Mink session manager.
   *
   * This is lazily initialized by the first call to self::drupalGet().
   *
   * @var \Behat\Mink\Mink|null
   */
  protected ?Mink $mink;

  /**
   * Retrieves a Drupal path.
   *
   * Requests are sent to the HTTP kernel.
   *
   * There is no logged in user. Use \Drupal\Tests\user\Traits\UserCreationTrait
   * to set a current user.
   *
   * There is no theme. To place blocks, a test must first install a theme and
   * set it as active.
   *
   * @param string|\Drupal\Core\Url $path
   *   Drupal path or URL to load into Mink controlled browser.
   * @param array $options
   *   (optional) Options to be forwarded to the URL generator.
   * @param string[] $headers
   *   An array containing additional HTTP request headers, the array keys are
   *   the header names and the array values the header values. This is useful
   *   to set for example the "Accept-Language" header for requesting the page
   *   in a different language. Note that not all headers are supported, for
   *   example the "Accept" header is always overridden by the browser. For
   *   testing REST APIs it is recommended to obtain a separate HTTP client
   *   using getHttpClient() and performing requests that way.
   *
   * @see \Drupal\Tests\BrowserTestBase::getHttpClient()
   */
  protected function drupalGet($path, array $options = [], array $headers = []): void {
    $session = $this->getSession();

    $session->visit($path);

    if ($this->htmlOutputEnabled) {
      $html_output = 'GET request to: ' . $path;
      $out = $session->getPage()->getContent();
      $html_output .= '<hr />' . $out;
      $html_output .= $this->getHtmlOutputHeaders();
      $this->htmlOutput($html_output);
    }
  }

  /**
   * Returns Mink session.
   *
   * @param string $name
   *   (optional) Name of the session. Defaults to the active session.
   *
   * @return \Behat\Mink\Session
   *   The active Mink session object.
   */
  public function getSession($name = NULL) {
    // Lazily initialize the Mink session. We do this because unlike Browser
    // tests where there should definitely be requests made, this is not
    // necessarily the case with Kernel tests.
    if (!isset($this->mink)) {
      $this->initMink();
      // Set up the browser test output file.
      $this->initBrowserOutputFile();
    }

    return $this->mink->getSession($name);
  }

  /**
   * Initializes Mink sessions.
   */
  protected function initMink(): void {
    $driver = $this->getDefaultDriverInstance();

    $selectors_handler = new SelectorsHandler([
      'hidden_field_selector' => new HiddenFieldSelector(),
    ]);
    $session = new Session($driver, $selectors_handler);
    $this->mink = new Mink();
    $this->mink->registerSession('default', $session);
    $this->mink->setDefaultSessionName('default');
  }

  /**
   * Gets an instance of the default Mink driver.
   *
   * @return \Behat\Mink\Driver\DriverInterface
   *   Instance of default Mink driver.
   *
   * @throws \InvalidArgumentException
   *   When provided default Mink driver class can't be instantiated.
   */
  protected function getDefaultDriverInstance() {
    $http_kernel = $this->container->get('http_kernel');
    $browserkit_client = new KernelTestHttpKernelBrowser($http_kernel);
    $driver = new BrowserKitDriver($browserkit_client);
    return $driver;
  }

  /**
   * Returns WebAssert object.
   *
   * @param string $name
   *   (optional) Name of the session. Defaults to the active session.
   *
   * @return \Drupal\Tests\WebAssert
   *   A new web-assert option for asserting the presence of elements with.
   */
  public function assertSession($name = NULL) {
    $this->addToAssertionCount(1);
    return new WebAssert($this->getSession($name));
  }

}
