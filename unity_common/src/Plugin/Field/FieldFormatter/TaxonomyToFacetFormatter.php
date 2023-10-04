<?php

namespace Drupal\unity_common\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\facets\FacetManager\DefaultFacetManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Taxonomy term to facet' formatter.
 *
 * @FieldFormatter(
 *   id = "Map taxonomy term to facet",
 *   label = @Translation("Unity taxonomy term to facet formatter"),
 *   field_types = {
 *     "entity_reference"
 *   },
 * )
 */
class TaxonomyToFacetFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Facet Manager.
   *
   * @var \Drupal\facets\FacetManager\DefaultFacetManager
   */
  protected $facetManager;

  /**
   * Constructs a new instance of the DefaultFacetManager.
   *
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   Field definition.
   * @param array $settings
   *   Field settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\facets\FacetManager\DefaultFacetManager $facet_manager
   *   The facet manager service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, DefaultFacetManager $facet_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
    $this->facetManager = $facet_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('facets.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'search_page_url' => '',
      'facets' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    // TODO: There is no form validator call for plugin settings forms,
    // possibly look at using an ajax callback to validate CSS classes.

    // Get all the enabled facets.
    $facets = $this->facetManager->getEnabledFacets();

    // Store the facet names for use in the form.
    $active_facet_names = [];
    foreach ($facets as $facet_name => $info) {
      $active_facet_names[$facet_name] = $info->get('name');
    }

    $elements['search_page_url'] = [
      '#title' => $this->t('Search page URL ending'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('search_page_url'),
      '#description' => $this->t('Add the url ending of the search page. e.g. If the the search page is http://www.test.co.uk/search-page then simply enter search-page.'),
      '#required' => TRUE,
    ];
    $elements['facets'] = [
      '#type' => 'select',
      '#title' => t('Related facet'),
      '#description' => t('The facet you want to link this term to.'),
      '#options' => $active_facet_names,
      "#default_value" => $this->getSetting('facets'),
      '#required' => TRUE,
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Search page URL ending: @search_page_url', ['@search_page_url' => $this->getSetting('search_page_url')]);
    $summary[] = $this->t('Facet: @facets', ['@facets' => $this->getSetting('facets')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $settings = $this->getSettings();
    // Get the facets ID from the form selection.
    $id = $settings['facets'];
    // Load the facet and get the URL alias.
    $facet = $this->entityTypeManager
      ->getStorage('facets_facet')
      ->load($id);
    $facet_pretty_path_url = $facet->get('url_alias');

    foreach ($items as $delta => $item) {
      // Get the taxonomy tid for this field.
      $tid = $item->target_id;
      if (!empty($tid)) {
        // Load up the taxonomy term so that we can get the name.
        $term = $this->entityTypeManager
          ->getStorage('taxonomy_term')
          ->load($tid);
        // Build the link to return to the search page with this term selected.
        $element[$delta] = [
          '#title' => t($term->label()),
          '#type' => 'link',
          '#url' => Url::fromUserInput('/' . $settings['search_page_url'] . '/' . $facet_pretty_path_url . '/' . $tid),
        ];
      }
    }

    return $element;
  }

}
