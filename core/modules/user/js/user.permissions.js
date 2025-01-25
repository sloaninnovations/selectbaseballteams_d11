/**
 * @file
 * User permission page behaviors.
 */

(function ($, Drupal, debounce) {
  /**
   * Shows checked and disabled checkboxes for inherited permissions.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches functionality to the permissions table.
   */
  Drupal.behaviors.permissions = {
    attach() {
      const [table] = once('permissions', 'table#permissions');
      if (!table) {
        return;
      }

      // Create fake checkboxes. We use fake checkboxes instead of reusing
      // the existing checkboxes here because new checkboxes don't alter the
      // submitted form. If we'd automatically check existing checkboxes, the
      // permission table would be polluted with redundant entries. This is
      // deliberate, but desirable when we automatically check them.
      const $fakeCheckbox = $(Drupal.theme('checkbox'))
        .removeClass('form-checkbox')
        .addClass('fake-checkbox js-fake-checkbox')
        .attr({
          disabled: 'disabled',
          checked: 'checked',
          title: Drupal.t(
            'This permission is inherited from the authenticated user role.',
          ),
        });
      const $wrapper = $('<div></div>').append($fakeCheckbox);
      const fakeCheckboxHtml = $wrapper.html();

      /**
       * Process each table row to create fake checkboxes.
       *
       * @param {object} object
       * @param {HTMLElement} object.target
       */
      function tableRowProcessing({ target }) {
        once('permission-checkbox', target).forEach((checkbox) => {
          checkbox
            .closest('tr')
            .querySelectorAll(
              'input[type="checkbox"]:not(.js-rid-anonymous, .js-rid-authenticated)',
            )
            .forEach((check) => {
              check.classList.add('real-checkbox', 'js-real-checkbox');
              check.insertAdjacentHTML('beforebegin', fakeCheckboxHtml);
            });
        });
      }

      // An IntersectionObserver object is associated with each of the table
      // rows to activate checkboxes interactively as users scroll the page
      // up or down. This prevents processing all checkboxes on page load.
      const checkedCheckboxObserver = new IntersectionObserver(
        (entries, thisObserver) => {
          entries
            .filter((entry) => entry.isIntersecting)
            .forEach((entry) => {
              tableRowProcessing(entry);
              thisObserver.unobserve(entry.target);
            });
        },
        {
          rootMargin: '50%',
        },
      );

      // Select rows with checked authenticated role and attach an observer
      // to each.
      table
        .querySelectorAll(
          'tbody tr input[type="checkbox"].js-rid-authenticated:checked',
        )
        .forEach((checkbox) => checkedCheckboxObserver.observe(checkbox));

      // Create checkboxes only when necessary on click.
      $(table).on(
        'click.permissions',
        'input[type="checkbox"].js-rid-authenticated',
        tableRowProcessing,
      );
    },
  };

  /**
   * Filters the permission list table by a text input search string.
   *
   * Text search input: input.table-filter-text
   * Target table:      input.table-filter-text[data-table]
   * Source text:       .table-filter-text-source
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.tableFilterByText = {
    attach(context, settings) {
      const [input] = once('table-filter-text', 'input.table-filter-text');
      if (!input) {
        return;
      }

      // Get the table and rows to filter.
      const tableSelector = input.getAttribute('data-table');
      const table = document.querySelector(tableSelector);
      const rows = table.querySelectorAll('tbody tr');

      // Trigger the search only after user typed at least x characters.
      const searchTriggerThreshold = 2;

      // Debounce the search to avoid performance issues.
      const debounceThreshold = 200;

      // Case insensitive expression to find query at the beginning of a word.
      const re = (query) => new RegExp(`\\b${query}`, 'i');

      // Parse all permissions' names and write them to an array.
      const sourceStrings = Array.from(
        table.querySelectorAll('.table-filter-text-source'),
      ).map((source) => {
        return {
          html: source,
          normalizedText: source.textContent,
        };
      });

      const updateTableRowVisibility = (row, visibility) => {
        row.style.display = visibility ? '' : 'none';
      };

      const resetRowsVisibility = () => {
        rows.forEach((row) => {
          row.style.display = '';
        });
      };

      // Store transitional information on filtered rows and query text.
      let filteredRowsTransitional = rows;
      let sourceStringsTransitional = sourceStrings;
      let queryTransitional = '';

      function hideEmptyPermissionHeader(row) {
        const tdsWithModuleClass = row.querySelectorAll('td.module');
        // Function to check if an element is visible (`display: block`).
        function isVisible(element) {
          return getComputedStyle(element).display !== 'none';
        }
        if (tdsWithModuleClass.length > 0) {
          // Find the next visible sibling `<tr>`.
          let nextVisibleSibling = row.nextElementSibling;
          while (nextVisibleSibling && !isVisible(nextVisibleSibling)) {
            nextVisibleSibling = nextVisibleSibling.nextElementSibling;
          }

          // Check if the next visible sibling has the "module" class in any of
          // its `<td>` elements.
          const nextVisibleSiblingHasModuleClass = nextVisibleSibling
            ? nextVisibleSibling.querySelector('td.module') !== null
            : false;

          // Check if this is the last visible row with class "module".
          const isLastVisibleModuleRow = !nextVisibleSibling;

          // Hide current row with class "module" if it meets the conditions.
          row.style.display =
            nextVisibleSiblingHasModuleClass || isLastVisibleModuleRow
              ? 'none'
              : '';
        }
      }

      // The function being requested on each keystroke by the user.
      function filterPermissionList(e) {
        const rawQuery = e.target.value;
        const query = re(rawQuery);

        // Stop immediately if nothing's changed.
        if (rawQuery === queryTransitional) return;

        // For performance, adjust the source strings to search from and rows sample.
        if (rawQuery.search(re(queryTransitional)) === -1) {
          filteredRowsTransitional = rows;
          sourceStringsTransitional = sourceStrings;
        }

        // If the query length is below threshold, show all table rows.
        if (query.length < searchTriggerThreshold) {
          resetRowsVisibility();
        }

        // Else, hide rows that don't match the query.
        else {
          const updatedSourceStrings = [];
          sourceStringsTransitional.forEach((source) => {
            const textMatch = source.normalizedText.search(query) !== -1;
            const closestTr = source.html.closest('tr');
            updateTableRowVisibility(closestTr, textMatch);

            if (textMatch) {
              updatedSourceStrings.push(source);
            }
          });
          sourceStringsTransitional = updatedSourceStrings;

          // Update visible rows based on the text being searched for.
          const visibleRows = Array.from(filteredRowsTransitional).filter(
            (row) => row.style.display !== 'none',
          );
          visibleRows.forEach(hideEmptyPermissionHeader);

          // Find elements with class "permission" within visible rows.
          if (visibleRows.length) {
            const tdsWithModuleOrPermissionClass = [];
            visibleRows.forEach((row) => {
              const tds = row.querySelectorAll('.permission');
              tdsWithModuleOrPermissionClass.push(...tds);
            });

            Drupal.announce(
              Drupal.formatPlural(
                tdsWithModuleOrPermissionClass.length,
                '1 permission is available in the modified list.',
                '@count permissions are available in the modified list.',
              ),
            );
          }
        }

        // Updates the transitional information.
        queryTransitional = rawQuery;
      }

      function preventEnterKey(event) {
        if (event.which === 13) {
          event.preventDefault();
          event.stopPropagation();
        }
      }

      if (table) {
        input.addEventListener(
          'keyup',
          debounce(filterPermissionList, debounceThreshold),
        );
        input.addEventListener(
          'click',
          debounce(filterPermissionList, debounceThreshold),
        );
        input.addEventListener('keydown', preventEnterKey);
      }
    },
  };
})(jQuery, Drupal, Drupal.debounce);
