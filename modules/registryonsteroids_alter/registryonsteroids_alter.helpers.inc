<?php

/**
 * @file
 * registryonsteroids_alter.helpers.inc
 */

/**
 * Extend the #theme_wrappers property of an element.
 *
 * @param array $element
 *   The element.
 * @param string[] $suggestions_parts
 *   The suggestions parts.
 */
function _registryonsteroids_alter_extend_theme_wrappers_property_with_suggestions(array &$element, array $suggestions_parts) {
  if (!isset($element['#theme_wrappers']) || !is_array($element['#theme_wrappers'])) {
    return;
  }

  $element['#theme_wrappers'] = array_map(
    function ($theme_hook) use ($suggestions_parts) {
      return _registryonsteroids_alter_extend_value_with_suggestions(
        $theme_hook,
        $suggestions_parts);
    },
    $element['#theme_wrappers']);
}

/**
 * Extend the #theme property of an element.
 *
 * @param array $element
 *   The element.
 * @param string[] $suggestions_parts
 *   The suggestions parts.
 */
function _registryonsteroids_alter_extend_theme_property_with_suggestions(array &$element, array $suggestions_parts) {
  if (!isset($element['#theme'])) {
    return;
  }

  if (is_string($element['#theme'])) {
    $element['#theme'] = _registryonsteroids_alter_extend_value_with_suggestions(
      $element['#theme'],
      $suggestions_parts);
  }

  if (is_array($element['#theme'])) {
    $element['#theme'] = array_map(
      function ($theme_hook) use ($suggestions_parts) {
        return _registryonsteroids_alter_extend_value_with_suggestions(
          $theme_hook,
          $suggestions_parts);
      },
      $element['#theme']
    );
  }
}

/**
 * Extends strings with an array of suggestions.
 *
 * @param string $base
 *   The string to extend.
 * @param string[] $suggestions_parts
 *   The suggestions to extend the base string with.
 *
 * @example
 *   $base = 'block';
 *   $suggestions_parts = array('system', 'main-menu');
 *
 *   The result will be: 'block__system__main_menu'
 *
 *   Quick note about the drupal_strtolower() call:
 *   We must ensure lowercase values because get_defined_functions()
 *   in drupal_group_functions_by_prefix() returns functions in lower case.
 *
 * @see http://php.net/manual/en/function.get-defined-functions.php
 *
 * @return array
 *   The extended strings.
 */
function _registryonsteroids_alter_extend_value_with_suggestions($base, array $suggestions_parts) {
  if (empty($base) || !is_string($base)) {
    // Make sure we do not modify the $base parameter be returning it if doesn't
    // pass this test.
    return $base;
  }

  array_unshift($suggestions_parts, $base);

  return str_replace(
    '-',
    '_',
    drupal_strtolower(
      implode(
        '__',
        array_map(
          function ($suggestion_part) {
            return trim($suggestion_part, '_');
          },
          array_filter(
            $suggestions_parts
          ))
      )
    )
  );
}

/**
 * Custom helper function.
 *
 * This helper updates the render array of a block so
 * block preprocess callbacks are correctly processed.
 *
 * @param array $block_array
 *   The original block render array.
 * @param string|null $region
 *   The region where the block is placed.
 */
function _registryonsteroids_alter_update_block_render_array(array &$block_array, $region = NULL) {
  $suggestions_parts = array(
    $region,
    $block_array['#block']->module,
    $block_array['#block']->delta,
  );

  if (isset($block_array['#theme'])) {
    $suggestions_parts = array_merge($suggestions_parts, (array) $block_array['#theme']);
  }

  _registryonsteroids_alter_extend_theme_wrappers_property_with_suggestions(
    $block_array,
    $suggestions_parts);
}

/**
 * Custom utility callback that apply to each array element, a callback.
 *
 * Unlike array_walk_recursive, this function send the key name if a value is
 * an array.
 * As the value is passed by reference, you are able to modify its value within
 * the callback.
 *
 * @param array $variables
 *   The array.
 * @param callable $callback
 *   The callback function.
 */
function registryonsteroids_recursive_foreach(array &$variables, callable $callback) {
  $variables['#ros_processed'] = TRUE;

  foreach ($variables as $key => &$value) {
    if (is_array($value) && !isset($value['#ros_processed'])) {
      registryonsteroids_recursive_foreach($value, $callback);
    }

    call_user_func_array($callback, array($key, &$value, &$variables));
  }

  unset($variables['#ros_processed']);
}

/**
 * Try to find information about the current page being viewed.
 *
 * This function will detect if an entity is displayed.
 * If an entity is displayed it will return an array containing:
 *  - The entity type
 *  - The entity info array
 *  - The entity itself.
 *
 * @return array|false
 *   The array if an entity is found, FALSE otherwise.
 */
function registryonsteroids_alter_menu_get_any_object() {
  $return = FALSE;
  $item = menu_get_item();
  $item['load_functions'] = (array) $item['load_functions'];
  $load_functions = array_values($item['load_functions']);

  if (NULL === $load_functions) {
    return $return;
  }

  $arg_position = array_search(
    $load_functions[0],
    $item['load_functions'],
    TRUE
  );

  foreach (entity_get_info() as $entity_name => $entity_info) {
    if ($entity_info['load hook'] !== $load_functions[0]) {
      continue;
    }

    if (($obj = menu_get_object($entity_name, $arg_position)) && is_object($obj)) {
      return array(
        'entity' => $obj,
        'entity type' => $entity_name,
        'entity info' => $entity_info,
      );
    }
  }

  return $return;
}
