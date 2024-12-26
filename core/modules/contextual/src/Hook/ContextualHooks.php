<?php

namespace Drupal\contextual\Hook;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for contextual.
 */
class ContextualHooks {

  /**
   * Implements hook_toolbar().
   */
  #[Hook('toolbar')]
  public function toolbar(): array {
    $items = [];
    $items['contextual'] = ['#cache' => ['contexts' => ['user.permissions']]];
    if (!\Drupal::currentUser()->hasPermission('access contextual links')) {
      return $items;
    }
    $items['contextual'] += [
      '#type' => 'toolbar_item',
      'tab' => [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#value' => t('Edit'),
        '#attributes' => [
          'class' => [
            'toolbar-icon',
            'toolbar-icon-edit',
          ],
          'aria-pressed' => 'false',
          'type' => 'button',
        ],
      ],
      '#wrapper_attributes' => [
        'class' => [
          'hidden',
          'contextual-toolbar-tab',
        ],
      ],
      '#attached' => [
        'library' => [
          'contextual/drupal.contextual-toolbar',
        ],
      ],
    ];
    return $items;
  }

  /**
   * Implements hook_page_attachments().
   *
   * Adds the drupal.contextual-links library to the page for any user who has the
   * 'access contextual links' permission.
   *
   * @see contextual_preprocess()
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$page): void {
    if (!\Drupal::currentUser()->hasPermission('access contextual links')) {
      return;
    }
    $page['#attached']['library'][] = 'contextual/drupal.contextual-links';
  }

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.contextual':
        $output = '';
        $output .= '<h2>' . t('About') . '</h2>';
        $output .= '<p>' . t('The Contextual links module gives users with the <em>Use contextual links</em> permission quick access to tasks associated with certain areas of pages on your site. For example, a menu displayed as a block has links to edit the menu and configure the block. For more information, see the <a href=":contextual">online documentation for the Contextual Links module</a>.', [':contextual' => 'https://www.drupal.org/docs/8/core/modules/contextual']) . '</p>';
        $output .= '<h2>' . t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . t('Displaying contextual links') . '</dt>';
        $output .= '<dd>';
        $output .= t('Contextual links for an area on a page are displayed using a contextual links button. There are two ways to make the contextual links button visible:');
        $output .= '<ol>';
        $sample_picture = [
          '#theme' => 'image',
          '#uri' => 'core/misc/icons/bebebe/pencil.svg',
          '#alt' => t('contextual links button'),
        ];
        $sample_picture = \Drupal::service('renderer')->render($sample_picture);
        $output .= '<li>' . t('Hovering over the area of interest will temporarily make the contextual links button visible (which looks like a pencil in most themes, and is normally displayed in the upper right corner of the area). The icon typically looks like this: @picture', ['@picture' => $sample_picture]) . '</li>';
        $output .= '<li>' . t('If you have the <a href=":toolbar">Toolbar module</a> installed, clicking the contextual links button in the toolbar (which looks like a pencil) will make all contextual links buttons on the page visible. Clicking this button again will toggle them to invisible.', [
          ':toolbar' => \Drupal::moduleHandler()->moduleExists('toolbar') ? Url::fromRoute('help.page', [
            'name' => 'toolbar',
          ])->toString() : '#',
        ]) . '</li>';
        $output .= '</ol>';
        $output .= t('Once the contextual links button for the area of interest is visible, click the button to display the links.');
        $output .= '</dd>';
        $output .= '</dl>';
        return $output;
    }
  }

  /**
   * Implements hook_contextual_links_view_alter().
   *
   * @see \Drupal\contextual\Plugin\views\field\ContextualLinks::render()
   */
  #[Hook('contextual_links_view_alter')]
  public function contextualLinksViewAlter(&$element, $items): void {
    if (isset($element['#contextual_links']['contextual'])) {
      $encoded_links = $element['#contextual_links']['contextual']['metadata']['contextual-views-field-links'];
      $element['#links'] = Json::decode(rawurldecode($encoded_links));
    }
  }

  /**
   * Implements hook_preprocess().
   *
   * @see \Drupal\contextual\Element\ContextualLinksPlaceholder
   * @see contextual_page_attachments()
   * @see \Drupal\contextual\ContextualController::render()
   */
  #[Hook('preprocess')]
  public function preprocess(&$variables, $hook, $info): void {
    // Determine the primary theme function argument.
    if (!empty($info['variables'])) {
      $keys = array_keys($info['variables']);
      $key = $keys[0];
    }
    elseif (!empty($info['render element'])) {
      $key = $info['render element'];
    }
    if (!empty($key) && isset($variables[$key])) {
      $element = $variables[$key];
    }

    if (isset($element) && is_array($element) && !empty($element['#contextual_links'])) {
      $variables['#cache']['contexts'][] = 'user.permissions';
      if (\Drupal::currentUser()->hasPermission('access contextual links')) {
        // Mark this element as potentially having contextual links attached to it.
        $variables['attributes']['class'][] = 'contextual-region';

        // Renders a contextual links placeholder unconditionally, thus not breaking
        // the render cache. Although the empty placeholder is rendered for all
        // users, contextual_page_attachments() only adds the asset library for
        // users with the 'access contextual links' permission, thus preventing
        // unnecessary HTTP requests for users without that permission.
        $variables['title_suffix']['contextual_links'] = [
          '#type' => 'contextual_links_placeholder',
          '#id' => _contextual_links_to_id($element['#contextual_links']),
        ];
      }
    }
  }

}
