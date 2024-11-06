<?php

namespace Drupal\Core\Form;

/**
 * Provides common functionality for form element options.
 */
class FormOptionsHelper {

  /**
   * Identifies a 'None' option key.
   */
  const OPTIONS_EMPTY_OPTION = '_none';

  /**
   * Converts an array of options into HTML, for use in select list form
   * elements.
   *
   * This function calls itself recursively to obtain the values for each
   * optgroup within the list of options and when the function encounters an
   * object with an 'options' property inside $element['#options'].
   *
   * @param array $element
   *   An associative array containing the following key-value pairs:
   *   - #multiple: Optional Boolean indicating if the user may select more than
   *     one item.
   *   - #options: An associative array of options to render as HTML. Each array
   *     value can be a string, an array, or an object with an 'option'
   *     property:
   *     - A string or integer key whose value is a translated string is
   *       interpreted as a single HTML option element. Do not use placeholders
   *       that sanitize data: doing so will lead to double-escaping. Note that
   *       the key will be visible in the HTML and could be modified by
   *       malicious users, so don't put sensitive information in it.
   *     - A translated string key whose value is an array indicates a group of
   *       options. The translated string is used as the label attribute for the
   *       optgroup. Do not use placeholders to sanitize data: doing so will
   *       lead to double-escaping. The array should contain the options you
   *       wish to group and should follow the syntax of $element['#options'].
   *     - If the function encounters a string or integer key whose value is an
   *       object with an 'option' property, the key is ignored, the contents of
   *       the option property are interpreted as $element['#options'], and the
   *       resulting HTML is added to the output.
   *   - #value: Optional integer, string, or array representing which option(s)
   *     to pre-select when the list is first displayed. The integer or string
   *     must match the key of an option in the '#options' list. If '#multiple'
   *     is TRUE, this can be an array of integers or strings.
   * @param array|null $choices
   *   (optional) Either an associative array of options in the same format as
   *   $element['#options'] above, or NULL. This parameter is only used
   *   internally and is not intended to be passed in to the initial function
   *   call.
   *
   * @return string
   *   An HTML string of options and optgroups for use in a select form element.
   */
  public static function formSelectOptions(array $element, $choices = NULL) {
    if (!isset($choices)) {
      if (empty($element['#options'])) {
        return [];
      }
      $choices = $element['#options'];
    }
    // array_key_exists() accommodates the rare event where $element['#value']
    // is NULL. isset() fails in this situation.
    $value_valid = isset($element['#value']) || array_key_exists('#value', $element);
    $value_is_array = $value_valid && is_array($element['#value']);
    // Check if the element is multiple select and no value has been selected.
    $empty_value = (empty($element['#value']) && !empty($element['#multiple']));
    $options = [];
    foreach ($choices as $key => $choice) {
      if (is_array($choice)) {
        $options[] = [
          'type' => 'optgroup',
          'label' => $key,
          'options' => static::formSelectOptions($element, $choice),
        ];
      }
      elseif (is_object($choice) && isset($choice->option)) {
        $options = array_merge($options, static::formSelectOptions($element, $choice->option));
      }
      else {
        $option = [];
        $key = (string) $key;
        $empty_choice = $empty_value && $key == static::OPTIONS_EMPTY_OPTION;
        if ($value_valid && ((!$value_is_array && (string) $element['#value'] === $key || ($value_is_array && in_array($key, $element['#value']))) || $empty_choice)) {
          $option['selected'] = TRUE;
        }
        else {
          $option['selected'] = FALSE;
        }
        $option['type'] = 'option';
        $option['value'] = $key;
        $option['label'] = $choice;
        $options[] = $option;
      }
    }

    return $options;
  }

  /**
   * Returns the indexes of a select element's options matching a given key.
   *
   * This function is useful if you need to modify the options that are already
   * in a form element; for example, to remove choices which are not valid
   * because of additional filters imposed by another module. One example might
   * be altering the choices in a taxonomy selector. To correctly handle the
   * case of a multiple hierarchy taxonomy, #options arrays can now hold an
   * array of objects, instead of a direct mapping of keys to labels, so that
   * multiple choices in the selector can have the same key (and label). This
   * makes it difficult to manipulate directly, which is why this helper
   * function exists.
   *
   * This function does not support optgroups (when the elements of the #options
   * array are themselves arrays), and will return FALSE if arrays are found.
   * The caller must either flatten/restore or manually do their manipulations
   * in this case, since returning the index is not sufficient, and supporting
   * this would make the "helper" too complicated and cumbersome to be of any
   * help.
   *
   * As usual with functions that can return array() or FALSE, do not forget to
   * use === and !== if needed.
   *
   * @param $element
   *   The select element to search.
   * @param $key
   *   The key to look for.
   *
   * @return array
   *   An array of indexes that match the given $key. Array will be empty if no
   *   elements were found. FALSE if optgroups were found.
   */
  public static function formGetOptions($element, $key) {
    $keys = [];
    foreach ($element['#options'] as $index => $choice) {
      if (is_array($choice)) {
        return FALSE;
      }
      elseif (is_object($choice)) {
        if (isset($choice->option[$key])) {
          $keys[] = $index;
        }
      }
      elseif ($index == $key) {
        $keys[] = $index;
      }
    }
    return $keys;
  }

}
