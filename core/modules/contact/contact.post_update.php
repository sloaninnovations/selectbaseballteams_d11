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
function contact_post_update_set_empty_values_to_null(&$sandbox = []) {
  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'contact_form', function (ContactFormInterface $contact_form): bool {
      $fields = [
        'redirect' => $contact_form->getRedirectPath(),
        'reply' => $contact_form->getReply(),
        'message' => $contact_form->getMessage(),
      ];

      $updated = FALSE;

      foreach ($fields as $field => $value) {
        if (trim($value) === '') {
          $contact_form->set($field, NULL);
          $updated = TRUE;
        }
      }

      return $updated;
    });
}
