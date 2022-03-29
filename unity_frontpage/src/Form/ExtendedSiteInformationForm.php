<?php

namespace Drupal\unity_frontpage\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Form\SiteInformationForm;

/**
 * Class ExtendedSiteInformationForm.
 *
 * @package Drupal\unity_frontpage\Form
 */
class ExtendedSiteInformationForm extends SiteInformationForm {

  /**
   * Add frontpage_title field to basic site settings front_page form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $site_config = $this->config('system.site');
    $form = parent::buildForm($form, $form_state);
    $form['front_page']['frontpage_title'] = [
      '#type' => 'textfield',
      '#title' => t('Front page title'),
      '#default_value' => $site_config->get('frontpage_title') ?: t('Welcome to @name site',
        ['@name' => $form['site_information']['site_name']['#default_value']]),
      '#description' => t("Optionally enter an appropriate title for the front page."),
    ];

    $form['front_page']['frontpage_show_title'] = [
      '#type' => 'checkbox',
      '#title' => t('Show front page title'),
      '#default_value' => $site_config->get('frontpage_show_title'),
      '#description' => t('The title will be visible on the front page when this box is checked.'),
    ];

    $default = $form['front_page']['frontpage_title']['#default_value'];

    $site_config->set('frontpage_title', $default)->save();

    return $form;
  }

  /**
   * Sets value of frontpage_title when saving form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('system.site')
      ->set('frontpage_title', $form_state->getValue('frontpage_title'))
      ->set('frontpage_show_title', $form_state->getValue('frontpage_show_title'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
