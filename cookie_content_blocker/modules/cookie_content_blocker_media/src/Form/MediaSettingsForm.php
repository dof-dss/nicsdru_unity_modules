<?php

namespace Drupal\cookie_content_blocker_media\Form;

use function array_column;
use function array_filter;
use function array_merge;
use function array_unique;
use function image_style_options;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form builder to manage settings for Cookie content blocker - Media.
 *
 * @package Drupal\cookie_content_blocker_media\Form
 */
class MediaSettingsForm extends ConfigFormBase {

  /**
   * The media source plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $mediaSourcePluginManager;

  /**
   * MediaSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $mediaSourcePluginManager
   *   The media source plugin manager.
   */
  public function __construct(ConfigFactoryInterface $configFactory, PluginManagerInterface $mediaSourcePluginManager) {
    parent::__construct($configFactory);
    $this->mediaSourcePluginManager = $mediaSourcePluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): MediaSettingsForm {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.media.source')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'cookie_content_blocker_media_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['cookie_content_blocker_media.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);
    $sources = array_filter($this->mediaSourcePluginManager->getDefinitions(), function ($definition) {
      return !empty($definition['providers']);
    });

    $form['providers'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#title' => $this->t('Providers'),
    ];

    $providers = array_unique(array_merge(...array_column($sources, 'providers')));
    foreach ($providers as $provider) {
      $form['providers'][$provider] = $this->providerFormContainer($provider);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $config = $this->config('cookie_content_blocker_media.settings');
    $config->set('providers', $form_state->getValue('providers'));
    $config->save();
  }

  /**
   * Create a container to configure provider settings.
   *
   * @param string $provider
   *   The name of the provider.
   *
   * @return array
   *   The event form container.
   */
  private function providerFormContainer(string $provider): array {
    $config = $this->config('cookie_content_blocker_media.settings')->get("providers.$provider");
    $container = [
      '#type' => 'details',
      '#title' => $this->t('Settings for %provider media', ['%provider' => $provider]),
      '#tree' => TRUE,
    ];

    $container['blocked'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Block %provider media', ['%provider' => $provider]),
      '#description' => $this->t('Enable blocking of all %provider media until consent is given.', ['%provider' => $provider]),
      '#default_value' => $config['blocked'] ?? FALSE,
    ];

    $container['show_preview'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show a preview for blocked content'),
      '#default_value' => $config['show_preview'] ?? FALSE,
    ];

    $container['preview_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose an image style to use for the preview.'),
      '#options' => image_style_options(FALSE),
      '#default_value' => $config['preview_style'] ?? 'blocked_media_teaser',
      '#states' => [
        'visible' => [
          ':input[name="providers[' . $provider . '][show_preview]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $container['blocked_message'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Message for blocked %provider media', ['%provider' => $provider]),
      '#description' => $this->t('When %provider media is blocked and a message is shown, this message will be shown.', ['%provider' => $provider]),
      '#default_value' => $config['blocked_message']['value'] ?? $this->t('You have not yet given permission to place the required cookies. Accept the required cookies to view this content.'),
      '#format' => $config['blocked_message']['format'] ?? NULL,
      '#states' => [
        'visible' => [
          ':input[name="providers[' . $provider . '][blocked]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $container;
  }

}
