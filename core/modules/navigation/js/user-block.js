/**
 * @file
 *
 * Customizes the user block to use the current username without affecting to cache.
 */

((Drupal, drupalSettings, once) => {
  /**
   * Replaces the generic User with the actual username.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Sets the username.
   */
  Drupal.behaviors.navigationUsername = {
    attach: (context, settings) => {
      if (settings?.navigation?.user) {
        once('user-block', '[data-user-block]', context).forEach(
          (userBlock) => {
            userBlock
              .querySelectorAll(
                '.toolbar-button--icon--navigation-user-links-user-wrapper [data-toolbar-text], .toolbar-popover__header [data-toolbar-text]',
              )
              .forEach((button) => {
                button.textContent = settings.navigation.user;
              });
          },
        );
      }
    },
  };
})(Drupal, drupalSettings, once);
