<?php

namespace Drupal\cookie_content_blocker\Render;

use function array_diff_key;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_unshift;
use function defined;
use function in_array;
use function is_callable;
use function uasort;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\cookie_content_blocker\BlockedLibraryManagerInterface;
use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Drupal\Core\Asset\AssetResolver;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Asset\AttachedAssetsInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\HtmlResponseAttachmentsProcessor as CoreHtmlResponseAttachmentsProcessor;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Processes attachments of HTML responses.
 *
 * @see \Drupal\Core\Render\HtmlResponseAttachmentsProcessor
 */
class HtmlResponseAttachmentsProcessor extends CoreHtmlResponseAttachmentsProcessor {

  /**
   * The drupal settings.
   *
   * @var array
   */
  protected static $drupalSettings = [
    'type' => 'setting',
    'group' => JS_SETTING,
    'weight' => 0,
    'browsers' => [],
    'data' => [],
    'position' => 'scripts_bottom',
  ];

  /**
   * The allowed assets.
   *
   * @var \Drupal\Core\Asset\AttachedAssetsInterface
   */
  protected $allowedAssets;

  /**
   * The blocked assets.
   *
   * @var \Drupal\Core\Asset\AttachedAssetsInterface
   */
  protected $blockedAssets;

  /**
   * The library manager.
   *
   * @var \Drupal\cookie_content_blocker\BlockedLibraryManagerInterface
   */
  protected $libraryManager;

  /**
   * Constructs a HtmlResponseAttachmentsProcessor object.
   *
   * @param \Drupal\cookie_content_blocker\BlockedLibraryManagerInterface $library_manager
   *   The library manager for blocked libraries.
   * @param \Drupal\Core\Asset\AssetResolverInterface $asset_resolver
   *   An asset resolver.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\Asset\AssetCollectionRendererInterface $css_collection_renderer
   *   The CSS asset collection renderer.
   * @param \Drupal\Core\Asset\AssetCollectionRendererInterface $js_collection_renderer
   *   The JS asset collection renderer.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(BlockedLibraryManagerInterface $library_manager, AssetResolverInterface $asset_resolver, ConfigFactoryInterface $config_factory, AssetCollectionRendererInterface $css_collection_renderer, AssetCollectionRendererInterface $js_collection_renderer, RequestStack $request_stack, RendererInterface $renderer, ModuleHandlerInterface $module_handler) {
    parent::__construct($asset_resolver, $config_factory, $css_collection_renderer, $js_collection_renderer, $request_stack, $renderer, $module_handler);
    $this->libraryManager = $library_manager;
    $this->blockedAssets = $this->allowedAssets = new AttachedAssets();
  }

  /**
   * Merge additional settings into the drupal settings.
   *
   * @param array $settings
   *   The settings to merge.
   */
  protected function mergeSettings(array $settings): void {
    self::$drupalSettings = NestedArray::mergeDeepArray([
        self::$drupalSettings, $settings
    ], TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function processAssetLibraries(AttachedAssetsInterface $assets, array $placeholders): array {
    if (!$this->libraryManager->hasBlockedLibraries()) {
      return parent::processAssetLibraries($assets, $placeholders);
    }

    $variables = [
      'styles' => [],
      'scripts' => [],
      'scripts_bottom' => [],
    ];

    $blocked_libraries = $this->libraryManager->getBlockedLibraries();
    $allowed_libraries = array_filter($assets->getLibraries(), function ($library) use ($blocked_libraries) {
      return !in_array($library, $blocked_libraries, TRUE);
    });

    $this->allowedAssets = $assets->setLibraries($allowed_libraries);
    $this->blockedAssets = AttachedAssets::createFromRenderArray(['#attached' => ['library' => $blocked_libraries]]);
    $this->blockedAssets->setAlreadyLoadedLibraries($allowed_libraries);

    foreach (array_keys($variables) as $type) {
      $process_method = 'process' . Container::camelize($type);
      if (!isset($placeholders[$type]) || !is_callable([$this, $process_method])) {
        continue;
      }

      $variables[$type] = $this->{$process_method}();
    }

    // After everything is processed we restore the drupal settings on its
    // original position, now containing our extra settings created by
    // placeholders as well.
    $settings = self::$drupalSettings;
    array_unshift($variables[$settings['position']], ...$this->renderAssetCollection([$settings], $this->jsCollectionRenderer));
    return $variables;
  }

  /**
   * Generate a placeholder script referencing to the original asset.
   *
   * @param array $asset
   *   The asset to create a placeholder for.
   *
   * @return array
   *   The placeholder render array.
   */
  private function generateAssetPlaceholder(array $asset): array {
    $id = Html::getUniqueId(Crypt::randomBytesBase64());
    $placeholder = [
      '#type' => 'html_tag',
      '#tag' => 'script',
      '#value' => '',
      '#attributes' => [
        'data-cookie-content-blocker-asset-id' => $id,
      ],
    ];

    $attached = [
      '#attached' => [
        'drupalSettings' => [
          'cookieContentBlocker' => [
            'blockedAssets' => [
              $id => (string) $this->renderer->renderPlain($asset),
            ],
          ],
        ],
      ],
    ];

    // Merge attached settings back into the original assets.
    $placeholder_asset = AttachedAssets::createFromRenderArray($attached);
    $this->allowedAssets->setSettings(NestedArray::mergeDeepArray([
        $placeholder_asset->getSettings(),
        $this->allowedAssets->getSettings()
    ], TRUE));
    $this->mergeSettings(['data' => $this->allowedAssets->getSettings()]);
    return $placeholder;
  }

  /**
   * Collects the CSS assets.
   *
   * @return array
   *   The CSS asset collection.
   */
  private function getCssAssetCollection(): array {
    $optimize_css = !defined('MAINTENANCE_MODE') && $this->config->get('css.preprocess');
    return $this->getMergedAndSortedAssets(...$this->resolveAssets([
        $this->assetResolver, 'getCssAssets'
        ], $optimize_css));
  }

  /**
   * Collects the JS assets.
   *
   * @param string $region
   *   The region to retrieve assets for. Can either be 'header' or 'footer'.
   *
   * @return array
   *   The JS asset collection for the given region.
   */
  private function getJsAssetCollection(string $region): array {
    static $header = NULL, $footer = NULL, $processed = FALSE;

    if ($processed) {
      return $$region ?? [];
    }

    $optimize_js = !defined('MAINTENANCE_MODE') && !\Drupal::state()->get('system.maintenance_mode') && $this->config->get('js.preprocess');
    [
      [$allowed_js_assets_header, $allowed_js_assets_footer],
      [$allowed_js_assets_header_raw, $allowed_js_assets_footer_raw],
      [$blocked_js_assets_header, $blocked_js_assets_footer],
    ] = $this->resolveAssets([$this->assetResolver, 'getJsAssets'], $optimize_js);

    // @codingStandardsIgnoreStart
    $header = $this->getMergedAndSortedAssets($allowed_js_assets_header, $allowed_js_assets_header_raw, $blocked_js_assets_header);
    $footer = $this->getMergedAndSortedAssets($allowed_js_assets_footer, $allowed_js_assets_footer_raw, $blocked_js_assets_footer);

    // We need to extract settings because we want to add extra settings
    // for generated placeholders, so we can identify them. Therefor we
    // render all the scripts first and attach the settings at the end, which
    // at that point will contain our placeholder settings.
    $settings = $header['drupalSettings'] ?? $footer['drupalSettings'] ?? [];
    $settings['position'] = array_key_exists('drupalSettings', $header) ? 'scripts' : 'scripts_bottom';
    $this->mergeSettings($settings);
    unset($header['drupalSettings'], $footer['drupalSettings']);

    $processed = TRUE;
    return $$region ?? [];
    // @codingStandardsIgnoreEnd
  }

  /**
   * Merges and sorts allowed and blocked assets back together.
   *
   * Marks blocked assets to be able to identify them.
   *
   * @param array $allowed_assets
   *   The allowed assets.
   * @param array $allowed_assets_raw
   *   The raw allowed assets (un-optimized) used for comparison/diff.
   * @param array $blocked_assets
   *   The blocked assets.
   *
   * @return array
   *   The merged and sorted assets,
   */
  private function getMergedAndSortedAssets(array $allowed_assets, array $allowed_assets_raw, array $blocked_assets): array {
    // Filter out assets that are allowed. This leaves us with only the
    // assets we want to block and (optional) additional dependent assets that
    // are not required by any other asset.
    $assets = array_merge($allowed_assets, $this->markAssetsAsBlocked(array_diff_key($blocked_assets, $allowed_assets_raw)));
    uasort($assets, [AssetResolver::class, 'sort']);
    return $assets;
  }

  /**
   * Mark all individual assets as blocked.
   *
   * Makes sure they are not preprocesses or cached.
   *
   * @param array $assets
   *   The assets to mark.
   *
   * @return array
   *   The marked assets.
   */
  private function markAssetsAsBlocked(array $assets): array {
    return array_map(function ($asset) {
      $asset['preprocess'] = $asset['cache'] = FALSE;
      $asset['is_blocked'] = TRUE;
      return $asset;
    }, $assets);
  }

  /**
   * Processes css assets and creates output for the 'styles' variable.
   *
   * @noinspection PhpUnusedPrivateMethodInspection
   *
   * @return array
   *   The output for the 'styles' variable.
   */
  private function processStyles(): array {
    return $this->renderAssetCollection($this->getCssAssetCollection(), $this->cssCollectionRenderer);
  }

  /**
   * Processes js assets and creates output for the 'scripts' variable.
   *
   * @noinspection PhpUnusedPrivateMethodInspection
   *
   * @return array
   *   The output for the 'styles' variable.
   */
  private function processScripts(): array {
    return $this->renderAssetCollection($this->getJsAssetCollection('header'), $this->jsCollectionRenderer);
  }

  /**
   * Processes js assets and creates output for the 'scripts_bottom' variable.
   *
   * @noinspection PhpUnusedPrivateMethodInspection
   *
   * @return array
   *   The output for the 'styles' variable.
   */
  private function processScriptsBottom(): array {
    return $this->renderAssetCollection($this->getJsAssetCollection('footer'), $this->jsCollectionRenderer);
  }

  /**
   * Renders asset collections and inserts placeholders for blocked assets.
   *
   * @param array $collection
   *   The asset collection to render.
   * @param \Drupal\Core\Asset\AssetCollectionRendererInterface $renderer
   *   The renderer to use to render the collection.
   *
   * @return array
   *   The render array for the asset collection.
   */
  private function renderAssetCollection(array $collection, AssetCollectionRendererInterface $renderer): array {
    $rendered = [[]];
    foreach ($collection as $asset) {
      $rendered_asset = $renderer->render([$asset]);
      $rendered[] = !empty($asset['is_blocked']) ? [$this->generateAssetPlaceholder(...$rendered_asset)] : $rendered_asset;
    }

    return array_merge(...$rendered);
  }

  /**
   * Resolves assets.
   *
   * @param callable $resolver
   *   The resolver to use to resolve the assets.
   * @param bool $optimize
   *   Whether to optimize the assets.
   *
   * @return array
   *   A keyed array containing:
   *    - (0) Array of (possibly) optimized allowed assets.
   *    - (1) Array of un-optimized/raw allowed assets.
   *    - (2) Array of un-optimized/raw blocked assets
   */
  private function resolveAssets(callable $resolver, bool $optimize): array {
    if (!is_callable($resolver)) {
      return [];
    }

    return [
      $resolver($this->allowedAssets, $optimize),
      $resolver($this->allowedAssets, FALSE),
      $resolver($this->blockedAssets, FALSE),
    ];
  }

}
