/**
 * @file
 * Maintains and deprecates Dialog jQuery events.
 */

(function ($, Drupal, once) {
  if (once('drupal-dialog-deprecation-listener', 'html').length) {
    const eventSpecial = {
      handle($event) {
        const $element = $($event.target);
        const event = $event.originalEvent;
        const dialog = event.dialog;
        const dialogArguments = [$event, dialog, $element, event?.settings];
        $event.handleObj.handler.apply(this, dialogArguments);
      },
    };

    $.event.special['dialog:beforecreate'] = eventSpecial;
    $.event.special['dialog:aftercreate'] = eventSpecial;
    $.event.special['dialog:beforeclose'] = eventSpecial;
    $.event.special['dialog:afterclose'] = eventSpecial;

    const listenDialogEvent = (event) => {
      const windowEvents = $._data(window, 'events');
      const isWindowHasDialogListener = windowEvents[event.type];
      if (isWindowHasDialogListener) {
        Drupal.deprecationError({
          message: `jQuery event ${event.type} is deprecated in 10.3.0 and is removed from Drupal:12.0.0. See https://www.drupal.org/node/3422670`,
        });
      }
    };

    [
      'dialog:beforecreate',
      'dialog:aftercreate',
      'dialog:beforeclose',
      'dialog:afterclose',
    ].forEach((e) => window.addEventListener(e, listenDialogEvent));
  }

  // jQuery UI dialog position deprecation.
  const originalOption = $.ui.dialog.prototype.option;

  $.ui.dialog.prototype.option = function (key, ...rest) {
    if (typeof key === 'object') {
      Object.keys(key).forEach((k) => {
        if (k === 'position') {
          Drupal.deprecationError({
            message: `Using 'position' option in dialog is deprecated and will be removed in future versions. See https://www.drupal.org/node/3474095`,
          });
        }
      });
    }

    return originalOption.apply(this, [key, ...rest]);
  };
})(jQuery, Drupal, once);
