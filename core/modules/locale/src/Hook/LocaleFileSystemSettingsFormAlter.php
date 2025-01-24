<?php

namespace Drupal\locale\Hook;

use Drupal\Component\FileSecurity\FileSecurity;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Hook implementations for locale.
 */
class LocaleFileSystemSettingsFormAlter {

  /**
   * Locale editable configs.
   */
  protected Config $editableLocaleSettings;

  /**
   * Constructor for the hook implementation.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   * @param \Psr\Log\LoggerInterface $fileSystemLogger
   *   The file system logger channel.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   */
  public function __construct(
    protected FileSystemInterface $fileSystem,
    #[Autowire(service: 'logger.channel.file_system')]
    protected LoggerInterface $fileSystemLogger,
    ConfigFactoryInterface $configFactory,
  ) {
    $this->editableLocaleSettings = $configFactory->getEditable('locale.settings');
  }

  /**
   * Implements hook_form_FORM_ID_alter() for system_file_system_settings().
   *
   * Add interface translation directory setting to directories configuration.
   */
  #[Hook('form_system_file_system_settings_alter')]
  public function formSystemFileSystemSettingsAlter(&$form, FormStateInterface $form_state) : void {
    $form['translation_path'] = [
      '#type' => 'textfield',
      '#title' => t('Interface translations directory'),
      '#default_value' => $this->editableLocaleSettings->get('translation.path'),
      '#maxlength' => 255,
      '#description' => t('A local file system path where interface translation files will be stored.'),
      '#required' => TRUE,
      '#after_build' => [[$this, 'checkDirectory']],
      '#weight' => 10,
    ];
    if ($form['file_default_scheme']) {
      $form['file_default_scheme']['#weight'] = 20;
    }
    $form['#submit'][] = 'locale_system_file_system_settings_submit';
  }

  /**
   * Checks the existence of the directory specified in $form_element.
   *
   * @internal
   *
   * @param array $form_element
   *   The form element containing the name of the directory to check.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form element.
   */
  public function checkDirectory(array $form_element, FormStateInterface $form_state): array {
    $directory = $form_element['#value'];
    if ($directory === '') {
      return $form_element;
    }

    if (!is_dir($directory) && !$this->fileSystem->mkdir($directory, NULL, TRUE)) {
      // If the directory does not exist and cannot be created.
      $form_state->setErrorByName($form_element['#parents'][0], t('The directory %directory does not exist and could not be created.', ['%directory' => $directory]));
      $this->fileSystemLogger->error('The directory %directory does not exist and could not be created.', ['%directory' => $directory]);
    }

    if (is_dir($directory) && !is_writable($directory) && !$this->fileSystem->chmod($directory)) {
      // If the directory is not writable and cannot be made so.
      $form_state->setErrorByName($form_element['#parents'][0], t('The directory %directory exists but is not writable and could not be made writable.', ['%directory' => $directory]));
      $this->fileSystemLogger->error('The directory %directory exists but is not writable and could not be made writable.', ['%directory' => $directory]);
    }
    elseif (is_dir($directory)) {
      if ($form_element['#name'] === 'file_public_path') {
        // Create public .htaccess file.
        FileSecurity::writeHtaccess($directory, FALSE);
      }
      else {
        // Create private .htaccess file.
        FileSecurity::writeHtaccess($directory);
      }
    }

    return $form_element;
  }

}
