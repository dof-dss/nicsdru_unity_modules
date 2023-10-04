<?php

namespace Drupal\unity_common\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\Entity\Facet;

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
class TaxonomyToFacetFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'search_page_url' => '',
        'facets' => ''
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    // TODO: There is no form validator call for plugin settings forms,
    // possibly look at using an ajax callback to validate CSS classes.

    /** @var \Drupal\facets\FacetManager\DefaultFacetManager $facet_manager */
    $facet_manager = \Drupal::service('facets.manager');

    // Get all the enabled facets.
    $facets = $facet_manager->getEnabledFacets();

    // Store the facet names for use in the form.
    $active_facet_names = [];
    foreach ($facets as $facet_name => $info) {
      $active_facet_names[$facet_name] .= $info->get('name');
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

    // Load the facet and get the URL alias.
    $facet = Facet::load($settings['facets']);
    $facet_pretty_path_url = $facet->get('url_alias');

    foreach ($items as $delta => $item) {
      // Get the taxonomy tid for this field.
      $tid = $item->target_id;
      if (!empty($tid)) {
        // Load up the taxonomy term so that we can get the name.
        $term = \Drupal::entityTypeManager()
          ->getStorage('taxonomy_term')
          ->load($tid);
        // Build the link to return to the search page with this term selected.
        $element[$delta] = ['#markup' => '<a href="/' . $settings['search_page_url'] . '/' . $facet_pretty_path_url . '/' . $tid . '">' . $term->label() . '</a>'];
      }
    }

    return $element;
  }

}
