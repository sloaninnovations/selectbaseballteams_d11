/**
 * @file
 * Provides a simple method to open a URL in a modal dialog.
 */

(function (Drupal) {
  /**
   * Ajax command to open URL in a modal dialog.
   *
   * @param {Drupal.Ajax} [ajax]
   *   An Ajax object.
   * @param {object} response
   *   The Ajax response.
   */
  Drupal.AjaxCommands.prototype.openDialogWithUrl = function (ajax, response) {
    const dialogOptions = response.dialogOptions || {};
    const elementSettings = {
      progress: { type: 'fullscreen' },
      dialogType: 'modal',
      dialog: dialogOptions,
      url: response.url,
      httpMethod: 'GET',
    };
    Drupal.ajax(elementSettings).execute();
  };
})(Drupal);
