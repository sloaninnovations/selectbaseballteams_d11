<?php

/**
 * @file
 * Post update functions for Layout Builder.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\layout_builder\Entity\LayoutEntityDisplayInterface;
use Drupal\layout_builder\SectionComponent;

/**
 * Implements hook_removed_post_updates().
 */
function layout_builder_removed_post_updates(): array {
  return [
    'layout_builder_post_update_rebuild_plugin_dependencies' => '9.0.0',
    'layout_builder_post_update_add_extra_fields' => '9.0.0',
    'layout_builder_post_update_section_storage_context_definitions' => '9.0.0',
    'layout_builder_post_update_overrides_view_mode_annotation' => '9.0.0',
    'layout_builder_post_update_cancel_link_to_discard_changes_form' => '9.0.0',
    'layout_builder_post_update_remove_layout_is_rebuilding' => '9.0.0',
    'layout_builder_post_update_routing_entity_form' => '9.0.0',
    'layout_builder_post_update_discover_blank_layout_plugin' => '9.0.0',
    'layout_builder_post_update_routing_defaults' => '9.0.0',
    'layout_builder_post_update_discover_new_contextual_links' => '9.0.0',
    'layout_builder_post_update_fix_tempstore_keys' => '9.0.0',
    'layout_builder_post_update_section_third_party_settings_schema' => '9.0.0',
    'layout_builder_post_update_layout_builder_dependency_change' => '9.0.0',
    'layout_builder_post_update_update_permissions' => '9.0.0',
    'layout_builder_post_update_make_layout_untranslatable' => '9.0.0',
    'layout_builder_post_update_override_entity_form_controller' => '10.0.0',
    'layout_builder_post_update_section_storage_context_mapping' => '10.0.0',
    'layout_builder_post_update_tempstore_route_enhancer' => '10.0.0',
    'layout_builder_post_update_timestamp_formatter' => '11.0.0',
    'layout_builder_post_update_enable_expose_field_block_feature_flag' => '11.0.0',
  ];
}

/**
 * Add third_party_settings key to all section components.
 */
function layout_builder_post_update_section_component_third_party(?array &$sandbox = NULL): void {
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);

  $callback = function (EntityViewDisplayInterface $display) {
    $needs_update = FALSE;

    // Only update entity view displays where Layout Builder is enabled.
    if ($display instanceof LayoutEntityDisplayInterface && $display->isLayoutBuilderEnabled()) {
      foreach ($display->getSections() as $section) {
        // Add a third_party_settings element to each section component.
        $components = $section->getComponents();
        foreach ($components as $delta => $component) {
          $components[$delta] = new SectionComponent(
            $component->getUuid(),
            $component->getRegion(),
            $component->getConfiguration(),
            $component->toArray()['additional']
          );
          // Depending on the state of the configuration of a site and when this
          // update is run, there might already be third party settings on a
          // section component. Retain them, if they exist.
          $tps_providers = $component->getThirdPartyProviders();
          foreach ($tps_providers as $provider) {
            foreach ($component->getThirdPartySettings($provider) as $key => $value) {
              $component->setThirdPartySetting($provider, $key, $value);
            }
          }
          // Flag this display as needing to be updated.
          $needs_update = TRUE;
        }
      }
    }

    return $needs_update;
  };

  $config_entity_updater->update($sandbox, 'entity_view_display', $callback);
}
