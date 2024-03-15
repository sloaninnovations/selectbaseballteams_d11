<?php

namespace Drupal\views\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command that sets the browser URL without refreshing the page.
 *
 * This command is implemented in Drupal.AjaxCommands.prototype.setBrowserUrl.
 */
class SetBrowserUrl implements CommandInterface {

  /**
   * The URL to be set in the browser.
   *
   * @var string
   */
  protected $url;

  /**
   * Constructs a new command instance.
   *
   * @param string $url
   *   The URL to be set in the browser
   */
  public function __construct(string $url) {
    $this->url = $url;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'setBrowserUrl',
      'url' => $this->url,
    ];
  }

}
