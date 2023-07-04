<?php

namespace Drupal\Core\Access;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface;
use Drupal\Core\Session\SessionConfigurationInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

/**
 * Processes the outbound route to handle the CSRF token.
 */
class RouteProcessorCsrf implements OutboundRouteProcessorInterface, TrustedCallbackInterface {

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * The session configuration.
   *
   * @var \Drupal\Core\Session\SessionConfigurationInterface
  */
  protected $sessionConfiguration;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a RouteProcessorCsrf object.
   *
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
   *   The CSRF token generator.
   * @param \Drupal\Core\Session\SessionConfigurationInterface $session_configuration
   *   The session configuration.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(CsrfTokenGenerator $csrf_token, SessionConfigurationInterface $session_configuration, RequestStack $request_stack) {
    $this->csrfToken = $csrf_token;
    $this->sessionConfiguration = $session_configuration;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($route_name, Route $route, array &$parameters, BubbleableMetadata $bubbleable_metadata = NULL) {
    // Per https://www.drupal.org/node/2319205 anonymous users do not need
    // CSRF checking.
    if ($route->hasRequirement('_csrf_token') && $this->sessionConfiguration->hasSession($this->requestStack->getCurrentRequest())) {
      $path = ltrim($route->getPath(), '/');
      // Replace the path parameters with values from the parameters array.
      foreach ($parameters as $param => $value) {
        $path = str_replace("{{$param}}", $value, $path);
      }
      // Adding this to the parameters means it will get merged into the query
      // string when the route is compiled.
      if (!$bubbleable_metadata) {
        $parameters['token'] = $this->csrfToken->get($path);
      }
      else {
        // Generate a placeholder and a render array to replace it.
        $placeholder = Crypt::hashBase64($path);
        $placeholder_render_array = [
          '#lazy_builder' => [
            'route_processor_csrf:renderPlaceholderCsrfToken',
            (array) $path,
          ],
        ];

        // Instead of setting an actual CSRF token as the query string, we set
        // the placeholder, which will be replaced at the very last moment. This
        // ensures links with CSRF tokens don't break cacheability.
        $parameters['token'] = $placeholder;
        $bubbleable_metadata->addAttachments(['placeholders' => [$placeholder => $placeholder_render_array]]);
      }
    }
  }

  /**
   * Gets a CSRF token for the given path.
   *
   * #lazy_builder callback; gets a CSRF token for the given path.
   *
   * @param string $path
   *   The path to get a CSRF token for.
   *
   * @return array
   *   A renderable array representing the CSRF token.
   */
  public function renderPlaceholderCsrfToken($path) {
    return [
      '#markup' => $this->csrfToken->get($path),
      // Tokens are per session.
      '#cache' => [
        'contexts' => [
          'session',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['renderPlaceholderCsrfToken'];
  }

}
