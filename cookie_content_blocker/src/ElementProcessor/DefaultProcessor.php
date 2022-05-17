<?php

namespace Drupal\cookie_content_blocker\ElementProcessor;

use function array_merge;
use function is_array;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class DefaultProcessor.
 *
 * Applies defaults to the blocker element as configured in settings. Makes
 * sure the element is wrapped as well.
 *
 * @package Drupal\cookie_content_blocker\ElementProcessor
 */
class DefaultProcessor extends ElementProcessorBase {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * Constructs a DefaultProcessor object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(array $element): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function processElement(array $element): array {
    $config = $this->configFactory->get('cookie_content_blocker.settings');

    $element['#theme_wrappers'] = $element['#theme_wrappers'] ?? [];
    $element['#theme_wrappers'][] = 'cookie_content_blocker_wrapper';

    if (!is_array($element['#cookie_content_blocker'])) {
      $element['#cookie_content_blocker'] = [];
    }

    $defaults = [
      'blocked_message' => $config->get('blocked_message'),
      'show_button' => $config->get('show_button'),
      'button_text' => $config->get('button_text'),
      'enable_click' => $config->get('enable_click_consent_change'),
      'show_placeholder' => TRUE,
      'preview' => [],
    ];

    $element['#cookie_content_blocker'] = array_merge($defaults, $element['#cookie_content_blocker']);
    return $element;
  }

}
