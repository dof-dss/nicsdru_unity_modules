<?php

namespace Drupal\unity_common\EventSubscriber;

use Drupal\Core\Config\ConfigCollectionInfo;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Config\Importer\MissingContentEvent;
use Drupal\Core\Site\Settings;
use Drupal\unity_common\UpdateConfigFromEnvironment;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A subscriber that overwrites Google Maps api keys on config import.
 */
class UpdateConfigAfterImport implements EventSubscriberInterface {

  /**
   * The upadte config from env service.
   *
   * @var \Drupal\unity_common\UpdateConfigFromEnvironment
   */
  protected $updateEnvService;

  /**
   * Constructs a new UpdateConfigAfterImport instance.
   *
   * @param \Drupal\unity_common\UpdateConfigFromEnvironment $update_service
   *   The entity type manager.
   */
  public function __construct(UpdateConfigFromEnvironment $update_service) {
    $this->updateEnvService = $update_service;
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    return [
      ConfigEvents::IMPORT => ['onConfigImport']
    ];
  }

  /**
   * Overwrite various settings when config is imported.
   *
   * @param \Drupal\Core\Config\ConfigImporterEvent $event
   *   The Event to process.
   */
  public function onConfigImport(ConfigImporterEvent $event) {
    $change_list = $event->getChangelist();
    if (!empty($change_list)) {
      if ((isset($change_list['update']) && ($change_list['update'][0] == 'google_analytics.settings')) ||
        (isset($change_list['create']) && ($change_list['create'][0] == 'google_analytics.settings'))) {
        if (Settings::get('subsite_id')) {
          $this->updateEnvService->updateApiKey('google_analytics.settings', 'account', Settings::get('subsite_id') . '_GOOGLE_ANALYTICS_KEY');
        }
      }
    }
  }

}
