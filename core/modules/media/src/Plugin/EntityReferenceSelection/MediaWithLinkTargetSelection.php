<?php

namespace Drupal\media\Plugin\EntityReferenceSelection;

/**
 * Limits selection of media entities to those that have a link target.
 *
 * When standalone URLs are:
 * - enabled, all media entities have a link target and will be returned
 * - disabled, only media entities using a media source plugin whose
 *   ::getMetadata() method computes a METADATA_ATTRIBUTE_LINK_TARGET
 *   should be returned, because only they can have link targets.
 *
 * @see \Drupal\media\MediaSourceInterface::METADATA_ATTRIBUTE_LINK_TARGET
 * @see \Drupal\media\Plugin\media\Source\OEmbed::getMetadata()
 * @see \Drupal\media\Entity\MediaLinkTarget
 *
 * @EntityReferenceSelection(
 *   id = "default:media_link_target",
 *   label = @Translation("Media with link target selection"),
 *   entity_types = {"media"},
 *   group = "default",
 * )
 */
class MediaWithLinkTargetSelection extends MediaSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    // phpcs:disable
    // @see \Drupal\media\MediaSourceBase::getMetadata()
    if (!\Drupal::config('media.settings')->get('standalone_url')) {
      // Follow-up issue: https://www.drupal.org/project/drupal/issues/3317769.
      // The logic for finding media entities (which are used to provide entity link suggestions in CKEditor)
      // should be at the API level for bundles. From the core, we enable node bundle selection only
      // @see ckeditor5_entity_bundle_info_alter() in ckeditor5.module.

      // To generates entity link suggestions for use by an autocomplete in CKEditor 5, an equivalent entity selection
      // plugin is selected, @see \Drupal\ckeditor5\Controller\EntityLinkSuggestionsController::getSuggestions.

      // This is an example to build and add logic to avoid finding media entities that are not linkable:
      // any media bundle whose media source does not compute a link target should be omitted.
      // $query->condition('bundle', 'document', '<>');
    }
    // phpcs:enable

    return $query;
  }

}
