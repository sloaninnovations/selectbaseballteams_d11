<?php

/**
 * @file
 * Post update functions for Contact.
 */

use Drupal\contact\ContactFormInterface;
use Drupal\Core\Config\Entity\ConfigEntityUpdater;

/**
 * Implements hook_removed_post_updates().
 */
function contact_removed_post_updates() {
  return [
    'contact_post_update_add_message_redirect_field_to_contact_form' => '9.0.0',
    'contact_post_update_set_empty_default_form_to_null' => '11.0.0',
  ];
}

/**
 * Updates Contact form's message, redirect and reply from '' to NULL.
 */
function contact_post_update_contact_form(&$sandbox = []) {
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);
  $config_entity_updater->update($sandbox, 'contact_form', function (ContactFormInterface $contact_form): bool {
    // @see \Drupal\contact\Entity\ContactForm::preSave()
    return $contact_form->get('redirect') === '' || $contact_form->get('reply') === '' || $contact_form->get('message') === '';
  });
}
