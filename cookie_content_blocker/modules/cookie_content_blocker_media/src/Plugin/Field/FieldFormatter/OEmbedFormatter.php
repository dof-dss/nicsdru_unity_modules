<?php

namespace Drupal\cookie_content_blocker_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\media\IFrameUrlHelper;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\media\Plugin\Field\FieldFormatter\OEmbedFormatter as CoreOEmbedFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'oembed' formatter.
 *
 * @FieldFormatter(
 *   id = "cookie_content_blocker_oembed",
 *   label = @Translation("Cookie Content Blocker - oEmbed content"),
 *   field_types = {
 *     "link",
 *     "string",
 *     "string_long",
 *   },
 * )
 */
class OEmbedFormatter extends CoreOEmbedFormatter {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs an OEmbedFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin ID for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\media\OEmbed\ResourceFetcherInterface $resource_fetcher
   *   The oEmbed resource fetcher service.
   * @param \Drupal\media\OEmbed\UrlResolverInterface $url_resolver
   *   The oEmbed URL resolver service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\media\IFrameUrlHelper $iframe_url_helper
   *   The iFrame URL helper service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, MessengerInterface $messenger, ResourceFetcherInterface $resource_fetcher, UrlResolverInterface $url_resolver, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, IFrameUrlHelper $iframe_url_helper, RendererInterface $renderer) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $messenger, $resource_fetcher, $url_resolver, $logger_factory, $config_factory, $iframe_url_helper);
    $this->configFactory = $config_factory;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): OEmbedFormatter {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('messenger'),
      $container->get('media.oembed.resource_fetcher'),
      $container->get('media.oembed.url_resolver'),
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('media.oembed.iframe_url_helper'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $element = parent::viewElements($items, $langcode);
    $config = $this->configFactory->get('cookie_content_blocker_media.settings');

    if (empty($element)) {
      return $element;
    }

    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    foreach ($items as $delta => $item) {
      if (!isset($element[$delta])) {
        continue;
      }

      /** @var \Drupal\media\MediaInterface $entity */
      /** @var \Drupal\media\Plugin\media\Source\OEmbedInterface $source */
      $entity = $item->getEntity();
      $source = $entity->getSource();

      $provider = $source->getMetadata($entity, 'provider_name');
      $settings = $config->get("providers.$provider");
      if (empty($settings['blocked'])) {
        continue;
      }

      $original = $element[$delta];
      $element[$delta]['#pre_render'] = $element[$delta]['#pre_render'] ?? [];
      $element[$delta]['#pre_render'][] = 'cookie_content_blocker.element.processor:processElement';
      $element[$delta]['#cookie_content_blocker'] = [
        'blocked_message' => $settings['blocked_message']['value'],
        'original_content' => $original,
      ];

      if (empty($settings['show_preview'])) {
        continue;
      }

      $element[$delta]['#cookie_content_blocker']['preview'] = [
        '#theme' => 'image_style',
        '#style_name' => $settings['preview_style'] ?? 'blocked_media_teaser',
        '#uri' => $source->getMetadata($entity, 'thumbnail_uri'),
      ];
    }

    $this->renderer->addCacheableDependency($element, $config);
    return $element;

  }

}
