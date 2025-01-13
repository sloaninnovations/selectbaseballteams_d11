/**
 * @file
 * Focus trap functionality.
 */

((Drupal, tabbable) => {
  /**
   * Focus Trap implementation.
   *
   * @namespace Drupal.focusTrap
   */
  Drupal.focusTrap = {};

  /**
   * Array of elements in which focus will be available.
   */
  let focusTrapElements = [];

  /**
   * Creates the focus trap.
   * @param {event} e - keydown event object
   */
  function createFocusTrap(e) {
    if (e.key === 'Tab') {
      const tabbableElements = [];

      focusTrapElements.forEach((element) => {
        tabbableElements.push(
          ...tabbable.tabbable(element, { includeContainer: true }),
        );
      });

      const firstTabbableEl = tabbableElements[0];
      const lastTabbableEl = tabbableElements[tabbableElements.length - 1];

      if (e.shiftKey) {
        if (document.activeElement === firstTabbableEl) {
          lastTabbableEl.focus();
          e.preventDefault();
        }
      } else if (document.activeElement === lastTabbableEl) {
        firstTabbableEl.focus();
        e.preventDefault();
      }
    }
  }

  /**
   * Add a focus trap.
   * @param {Array} elements - array of elements in which any tabbable elements
   * will still be tabbable.
   */
  Drupal.focusTrap.add = (elements) => {
    focusTrapElements = elements;
    document.addEventListener('keydown', createFocusTrap);
  };

  /**
   * Remove the focus trap.
   */
  Drupal.focusTrap.remove = () => {
    document.removeEventListener('keydown', createFocusTrap);
  };
})(Drupal, tabbable);
