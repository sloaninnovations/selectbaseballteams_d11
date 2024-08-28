/**
 * Ajax command for highlighting elements.
 *
 * @param {Drupal.Ajax} [ajax]
 *   An Ajax object.
 * @param {object} response
 *   The Ajax response.
 * @param {string} response.id
 *   The row id.
 * @param {string} response.tabledrag_instance
 *   The tabledrag instance identifier.
 * @param {number} [status]
 *   The HTTP status code.
 */
Drupal.AjaxCommands.prototype.tabledragChanged = function (
  ajax,
  response,
  status,
) {
  if (status !== 'success') {
    return;
  }

  const tableDrag = Drupal.TableDrag.instances[response.tabledrag_instance];

  // eslint-disable-next-line new-cap
  const rowObject = tableDrag.row(
    document.getElementById(response.id),
    '',
    tableDrag.indentEnabled,
    tableDrag.maxDepth,
    true,
  );
  rowObject.markChanged();
  tableDrag.addChangedWarning();
};
