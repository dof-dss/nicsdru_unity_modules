<?php

namespace Drupal\cookie_content_blocker\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginCssInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the DraftText CKEditor plugin.
 *
 * @package Drupal\cookie_content_blocker\PLugin\CKEditorPlugin
 *
 * @CKEditorPlugin(
 *   id = "cookiecontentblocker",
 *   label = @Translation("Cookie Content Blocker"),
 *   module = "cookie_content_blocker"
 * )
 */
class CookieContentBlocker extends CKEditorPluginBase implements CKEditorPluginCssInterface {

  /**
   * The path of this module.
   *
   * @var string
   */
  protected $modulePath;

  /**
   * Constructs the Cookie Conten Blocked CKEditor Plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // Remove this deprecated code ('drupal_get_path')
    //$this->modulePath = drupal_get_path('module', 'cookie_content_blocker');
    // Replace with a call to a Drupal service, which is not done in the correct way
    // using Dependency Injection, but that would involve a lot of work and this is just
    // a quick fix to this contrib module in order to get it through our CI checks.
    $this->modulePath = \Drupal::service('extension.path.resolver')->getPath('module', 'cookie_content_blocker');
  }

  /**
   * {@inheritdoc}
   */
  public function getCssFiles(Editor $editor): array {
    return [
      $this->modulePath . '/js/plugins/cookiecontentblocker/css/editor.css',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile(): string {
    return $this->modulePath . '/js/plugins/cookiecontentblocker/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons(): array {
    return [
      'CookieContentBlocker' => [
        'label' => $this->t('Add Cookie Content Blocker content'),
        'image' => $this->modulePath . '/js/plugins/cookiecontentblocker/icons/cookiecontentblocker.png',
      ],
    ];
  }

}
