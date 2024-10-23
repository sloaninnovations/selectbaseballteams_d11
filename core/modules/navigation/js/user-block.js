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
  Drupal.behaviors.safeTriangleInit = {
    attach: (context) => {
      once('user-block', '[data-user-block]', context).forEach((userBlock) => {
        userBlock
          .querySelectorAll(
            'li > button .toolbar-button__label, li > .toolbar-popover__wrapper .toolbar-popover__header .toolbar-button__label, li > a .toolbar-button__label',
          )
          .forEach((button) => {
            button.textContent = drupalSettings.navigation.user;
          });
      });
    },
  };
})(Drupal, drupalSettings, once);
