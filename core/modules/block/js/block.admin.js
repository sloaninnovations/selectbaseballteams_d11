/**
 * @file
 * Block admin behaviors.
 */

(function ($, Drupal, debounce, once) {
  /**
   * Filters the block list by a text input search string.
   *
   * The text input will have the selector `input.block-filter-text`.
   *
   * The target element to do searching in will be in the selector
   * `input.block-filter-text[data-element]`
   *
   * The text source where the text should be found will have the selector
   * `.block-filter-text-source`
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the block filtering.
   */
  Drupal.behaviors.blockFilterByText = {
    attach(context, settings) {
      const $input = $(once('block-filter-text', 'input.block-filter-text'));
      const $table = $($input.attr('data-element'));
      let $filterRows;

      /**
       * Filters the block list.
       *
       * @param {jQuery.Event} e
       *   The jQuery event for the keyup event that triggered the filter.
       */
      function filterBlockList(e) {
        const query = e.target.value.toLowerCase();

        /**
         * Shows or hides the block entry based on the query.
         *
         * @param {number} index
         *   The index in the loop, as provided by `jQuery.each`
         * @param {HTMLElement} label
         *   The label of the block.
         */
        function toggleBlockEntry(index, label) {
          const $row = $(label).parent().parent();
          const textMatch = label.textContent.toLowerCase().includes(query);
          $row.toggle(textMatch);
        }

        // Filter if the length of the query is at least 2 characters.
        if (query.length >= 2) {
          $filterRows.each(toggleBlockEntry);
          Drupal.announce(
            Drupal.formatPlural(
              $table.find('tr:visible').length - 1,
              '1 block is available in the modified list.',
              '@count blocks are available in the modified list.',
            ),
          );
        } else {
          $filterRows.each(function (index) {
            $(this).parent().parent().show();
          });
        }
      }

      if ($table.length) {
        $filterRows = $table.find('div.block-filter-text-source');
        $input.on('keyup', debounce(filterBlockList, 200));
      }
    },
  };

  /**
   * Highlights the block that was just placed into the block listing.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the block placement highlighting.
   */
  Drupal.behaviors.blockHighlightPlacement = {
    attach(context, settings) {
      // Ensure that the block we are attempting to scroll to actually exists.
      if (settings.blockPlacement && $('.js-block-placed').length) {
        once(
          'block-highlight',
          '[data-drupal-selector="edit-blocks"]',
          context,
        ).forEach((container) => {
          const $container = $(container);
          window.scrollTo({
            top:
              $('.js-block-placed').offset().top -
              $container.offset().top +
              $container.scrollTop(),
            behavior: 'smooth',
          });
        });
      }
    },
  };

  /**
   * Filter the block list on block layout page by a text input search string.
   *
   * The target elements to search are block label and block region name.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the block filtering on block layout page.
   */
  Drupal.behaviors.blockFilterRegionText = {
    attach(context) {
      const gotoFiltered = document.getElementById('goto-filtered');
      const $inputFilter = once(
        'block-filter-region-text',
        '[data-drupal-selector="edit-search-blocks"]',
      );

      if ($inputFilter.length === 0) {
        return;
      }
      const inputFilterElement = $inputFilter[0];

      /**
       * Function to count the blocks and their status by region.
       *
       * @param {string} regionName
       *   The name of the region to get the status.
       * @return {{total: number, visible: number, invisible: number}}
       *   Return an object with regions blocks status.
       */
      const getRegionBlocksStatus = (regionName) => {
        const blocks = document.querySelectorAll(
          `tr[data-parent-region="${regionName}"]`,
        );
        const blocksVisible = Array.from(blocks).filter(
          (tr) => tr.style.display !== 'none',
        ).length;
        return {
          blocksAmount: blocks.length,
          blocksVisible,
          blocksInvisible: blocks.length - blocksVisible,
        };
      };

      const preventEnter = (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          e.stopPropagation();
        }
        if (e.key === 'Tab') {
          e.preventDefault();
          gotoFiltered.focus();
        }
      };

      /**
       * Function to reset the state of blocks and filter control.
       *
       * Reset all button elements to closed eyes icon and make any row,
       * be hidden by the filter.
       */
      const resetBlockRegionState = () => {
        document
          .querySelectorAll('.js-override-filter-visibility')
          .forEach((el) =>
            el.classList.remove('js-override-filter-visibility'),
          );
        document
          .querySelectorAll('.wrap-toggle-blocks-filtered')
          .forEach((el) =>
            el.classList.remove('wrap-toggle-blocks-filtered-opened'),
          );
      };

      /**
       * Update regions and their blocks states.
       *
       * Each region updating the filtered blocks message status.
       * Also, update the region empty message status.
       *
       * @param {string} query
       *   The value of the filter typed by the user.
       * @param {Node} table
       *   The updated dom element block with all regions.
       */
      const updateBlocksFilteredStatus = (query, table) => {
        table.querySelectorAll('.region-title').forEach((el) => {
          const currentRegionName = el.dataset.region;
          // Update the region filter status.
          const filteredRegionStatus = document.querySelector(
            `[data-drupal-selector="region-filtered-quantity-${currentRegionName}"]`,
          );
          const wrapToggleBLocks = el.querySelector(
            '.wrap-toggle-blocks-filtered',
          );

          const { blocksAmount, blocksVisible, blocksInvisible } =
            getRegionBlocksStatus(currentRegionName);

          if (blocksAmount > 0) {
            if (query !== '' && blocksAmount !== blocksVisible) {
              filteredRegionStatus.parentElement.parentElement.classList.add(
                'js-show-filtered-quantity',
              );
              wrapToggleBLocks.classList.add('region-filtered');
            } else {
              filteredRegionStatus.parentElement.parentElement.classList.remove(
                'js-show-filtered-quantity',
              );
              if (query === '') {
                wrapToggleBLocks.classList.remove('region-filtered');
              }
            }
            filteredRegionStatus.textContent = Drupal.t('@invisible filtered', {
              '@invisible': blocksInvisible,
            });
          }

          const regionEmptyMessage = el.nextElementSibling.nextElementSibling;
          let showEmptyRegion = false;
          if (blocksAmount === 0) {
            wrapToggleBLocks.classList.remove(
              'wrap-toggle-blocks-filtered-opened',
            );
            wrapToggleBLocks.classList.remove('region-filtered');
            showEmptyRegion = true;
          }
          regionEmptyMessage.style.display = showEmptyRegion ? '' : 'none';
        });
      };

      /**
       * Highlight and focus the first region with a block found.
       */
      const highlightBlocksFound = (e) => {
        if (!gotoFiltered.classList.contains('has-block-results')) {
          return;
        }
        let firstVisibleRegion = null;
        // Highlight blocks matched filter before go to.
        document.querySelectorAll('.js-filter-block-visible').forEach((el) => {
          if (!firstVisibleRegion) {
            firstVisibleRegion = el;
            el.querySelector('.js-tabledrag-handle').focus();
          }
          el.classList.add('color-success');
        });
      };

      /**
       * Filter all draggable rows on the table.
       *
       * When filtering the row will be keep as visible if,
       * The name of the block/region matches with text typed.
       * Or, if the filtered control foe the regions is turned on,
       * no matter if block name and region match with typed text,
       * all the blocks of that region will be displayed.
       *
       * @param {string} query
       *   The text type.
       */
      const filterCallback = (query = null) => {
        if (query === null) {
          query = document
            .querySelector('[data-drupal-selector="edit-search-blocks"]')
            .value.toLowerCase();
        }

        // Make sure highlighted blocks by go to filter action back to initial state.
        document.querySelectorAll('.js-filter-block-visible').forEach((el) => {
          el.classList.remove('color-success');
        });

        const table = document.getElementById('blocks');
        const listItems = table.querySelectorAll('tbody tr.draggable');

        if (listItems.length === 0) {
          return;
        }

        const blockFoundCount = {};
        listItems.forEach((tr) => {
          try {
            // Query the block label and region name.
            const textToBeQueried = `${tr.children[0].textContent} ${tr.children[1].textContent}`;
            // If the user clicked to display all blocks of the regions,
            // keep this blocks visible no matter if the query filter matched.
            const isVisibleByFilter = tr.classList.contains(
              'js-override-filter-visibility',
            );

            const textMatched = textToBeQueried.toLowerCase().includes(query);
            tr.classList.toggle('js-filter-block-visible', textMatched);
            let displayStatus = 'none';
            if (textMatched || isVisibleByFilter) {
              displayStatus = '';
              const region = tr.dataset.parentRegion;
              if (blockFoundCount[region] === undefined) {
                blockFoundCount[region] = 0;
              }
              blockFoundCount[region]++;
            }
            tr.style.display = displayStatus;
          } catch (error) {
            // If a problem occurs, default to showing the row.
            tr.style.display = '';
          }
        });

        // Update all regions status after update blocks visibility.
        updateBlocksFilteredStatus(query, table);

        const blocksVisible = Object.values(blockFoundCount).reduce(
          (acc, item) => acc + item,
          0,
        );

        // Update status of goto filtered element.
        if (query !== '') {
          let announcement = Drupal.t(
            'There are no blocks matching the filter conditions.',
          );
          if (blocksVisible === 0) {
            gotoFiltered.textContent = Drupal.t(
              'There are no blocks matching the filter conditions.',
            );
            gotoFiltered.style.display = 'block';
            gotoFiltered.classList.remove('has-block-results');
          } else {
            gotoFiltered.classList.add('has-block-results');
            gotoFiltered.textContent = Drupal.t('Go to items found.');
            gotoFiltered.style.display = 'block';
            const regionsFound = Object.keys(blockFoundCount);
            announcement = Drupal.t('1 block found on the region: @region', {
              '@region': regionsFound,
            });
            if (blocksVisible > 1) {
              announcement = Drupal.t(
                `@blocks blocks were found on the following ${regionsFound.length > 1 ? 'regions' : 'region'}: @regions`,
                {
                  '@blocks': blocksVisible,
                  '@regions': regionsFound.join(', ').replaceAll('_', ' '),
                },
              );
            }
          }
          Drupal.announce(announcement);
        }
      };

      // In some cases the blocks searched are on the bottom of page,
      // to help users get there faster they can click on the link
      // below the input filter.
      gotoFiltered.addEventListener('click', highlightBlocksFound);
      gotoFiltered.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          highlightBlocksFound(e);
        }
      });

      // Update all blocks by regions showing or hiding them based on the action value.
      const toggleBlocksByRegion = (region, action) => {
        if (inputFilterElement.value === '') {
          return;
        }
        document
          .querySelector(
            `#edit-blocks-region-${region.replace(
              '_',
              '-',
            )}-title .wrap-toggle-blocks-filtered`,
          )
          .classList[action]('wrap-toggle-blocks-filtered-opened');
        document
          .querySelectorAll(`tr[data-parent-region="${region}"]`)
          .forEach((tr) => {
            tr.classList[action]('js-override-filter-visibility');
          });
      };

      // Users can override the filter clicking on the eye action.
      // Toggling this element will display all blocks on the region,
      // no matters if filter applied.
      // When the filter is cleaned this button back to hidden states.
      document
        .querySelectorAll('.wrap-toggle-blocks-filtered:not(.built)')
        .forEach((row) => {
          const toggleButton = row.querySelector(
            '.toggle-blocks-filtered-input',
          );
          row.classList.add('built');
          toggleButton.addEventListener('click', (e) => {
            // element.ariaPressed = element.ariaPressed !== 'true';
            const action = row.classList.contains(
              'wrap-toggle-blocks-filtered-opened',
            )
              ? 'remove'
              : 'add';
            if (action === 'add') {
              toggleButton.value = Drupal.t('Hide filtered');
            } else {
              toggleButton.value = Drupal.t('Show filtered');
            }
            toggleBlocksByRegion(row.dataset.toggleRegion, action);
            filterCallback();
            e.preventDefault();
          });
        });

      inputFilterElement.addEventListener(
        'keyup',
        debounce((e) => filterCallback(e.target.value.toLowerCase()), 200),
      );

      // Add event to clear HTML5 x button.
      // But do the filter only on the click button because we already,
      // searching on keyup event with debounce.
      inputFilterElement.addEventListener('search', (e) => {
        if (e.target.value === '') {
          gotoFiltered.style.display = 'none';
          filterCallback('');
          resetBlockRegionState();
        }
      });
      inputFilterElement.addEventListener('keydown', preventEnter);
      inputFilterElement.addEventListener('keyup', (e) => {
        if (e.target.value === '') {
          gotoFiltered.style.display = 'none';
          resetBlockRegionState();
        }
      });

      // Extend block table drag event adding the filter callback.
      document.querySelectorAll('.draggable').forEach((row) => {
        row.addEventListener('blocksDropped', (e) => {
          filterCallback();
        });
      });

      // Do the filter after region changed by select field.
      const $selectRegionChange = once(
        'block-region-select-filter',
        'select.block-region-select',
      );
      if ($selectRegionChange.length > 0) {
        $selectRegionChange.forEach((selectElement) => {
          selectElement.addEventListener('change', () => {
            filterCallback(inputFilterElement.value);
          });
        });
      }
    },
  };
})(jQuery, Drupal, Drupal.debounce, once);
