<?php

namespace Drupal\cookie_content_blocker\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use function base64_decode;
use function base64_encode;
use function is_string;
use function json_decode;
use function preg_match_all;
use function str_replace;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Text filter that converts custom HTML tags into markup.
 *
 * @Filter(
 *   id = "cookie_content_blocker_filter",
 *   title = @Translation("Cookie content blocker filter"),
 *   description = @Translation("This filter converts Cookie content blocker's custom HTML tags into markup and handles blocking of wrapped HTML/Text. It is recommended to run this filter last."),
 *   type = \Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class CookieContentBlockerFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  private $renderer;

  /**
   * Construct a CookieContentBlockerFilter plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): CookieContentBlockerFilter {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['disable_xss'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable XSS filtering for tag settings'),
      '#description' => $this->t('Allow markup and scripts within the tag settings.'),
      '#default_value' => $this->settings['disable_xss'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode): FilterProcessResult {
    $result = new FilterProcessResult($text);
    if (empty($text)) {
      return $result;
    }

    return $result->setProcessedText($this->replaceTags($text));
  }

  /**
   * Replaces cookie content blocker tags for the given text.
   *
   * @param string $text
   *   The HTML/Text where we want to replace tags for.
   *
   * @return string
   *   The text with tags replaced.
   */
  private function replaceTags(string $text): string {
    [$matches, $settings, $content] = $this->matchTags($text);
    if (empty($matches)) {
      return $text;
    }

    foreach ($matches as $index => $match) {
      if (empty($content[$index])) {
        continue;
      }

      $blocker_settings = isset($settings[$index]) ? $this->decodeSettings($settings[$index]) : [];
      $blocked_content_element = [
        // We depend on other filters to have sanitized the content.
        '#markup' => Markup::create($content[$index]),
        '#pre_render' => ['cookie_content_blocker.element.processor:processElement'],
        '#cookie_content_blocker' => $blocker_settings ?: TRUE,
      ];

      $text = str_replace($match, $this->renderer->renderPlain($blocked_content_element), $text);
    }

    return $text;
  }

  /**
   * Match all our custom HTML element nodes with their settings and children.
   *
   * We use our own custom HTML element node '<cookiecontentblocker>'
   * using a node element makes it easier to work with in WYSIWYG editors.
   *
   * @param string $text
   *   The HTML/Text string.
   *
   * @return array
   *   A keyed array containing:
   *    - (0) Array of full pattern matches.
   *    - (1) Array of strings defining settings for each associated match.
   *    - (2) Array of strings defining the content for each associated match.
   */
  private function matchTags(string $text): array {
    preg_match_all('/<cookiecontentblocker.*?(?:data-settings="(.*?)".*?)*?>(.*?)<\/cookiecontentblocker>/s', $text, $matches);
    return [$matches[0], $matches[1], $matches[2]];
  }

  /**
   * Decode a encoded settings string to a settings array.
   *
   * @param string $settings
   *   The settings string to decode.
   *
   * @return array
   *   The decoded settings or an empty array if decoding failed.
   */
  private function decodeSettings(string $settings): array {
    // Check if we need to decode the settings first. This way we support
    // both base64 encoded strings and settings already in a JSON-string format.
    $decoded_settings = base64_decode($settings);
    $needs_decode = base64_encode($decoded_settings) === $settings;
    if ($needs_decode && is_string($decoded_settings)) {
      $settings = $decoded_settings;
    }

    if (empty($this->settings['disable_xss']) || $this->settings['disable_xss'] === FALSE) {
      $settings = Xss::filter($settings, []);
    }

    $settings = json_decode($settings, TRUE);
    return $settings ?: [];
  }

}
