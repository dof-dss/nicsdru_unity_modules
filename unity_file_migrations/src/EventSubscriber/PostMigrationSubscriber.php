<?php

namespace Drupal\unity_file_migrations\EventSubscriber;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Site\Settings;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class PostMigrationSubscriber.
 *
 * Post Migrate processes.
 */
class PostMigrationSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\Core\Logger\LoggerChannel definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * Stores the entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The legacy database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $dbConnD7;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $dbConnD10;

  /**
   * PostMigrationSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger
   *   Drupal logger.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              LoggerChannelFactory $logger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger->get('unity_file_migrations');
    // Open the Liofa legacy database if we are running inside the Liofa
    // site and there is a connection to 'liofa7', otherwise just open
    // 'default' twice and the Liofa check will fail in onMigratePostImport().
    $legacy_db = 'default';
    foreach (\Drupal\Core\Database\Database::getAllConnectionInfo() as $key => $targets) {
      if ($key == 'liofa7') {
        $legacy_db  = $key;
      }
    }
    $this->dbConnD7 = Database::getConnection('default', $legacy_db);
    $this->dbConnD10 = Database::getConnection('default', 'default');
  }

  /**
   * Get subscribed events.
   *
   * @inheritdoc
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::POST_IMPORT][] = ['onMigratePostImport'];
    return $events;
  }

  /**
   * Handle post import migration event.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The import event object.
   */
  public function onMigratePostImport(MigrateImportEvent $event) {
    $event_id = $event->getMigration()->getBaseId();

    // If we have just migrated aliases for Liofa then we need
    // tidy them up afterwards.
    if ($event_id === 'upgrade_d7_url_alias') {
      $site = Settings::get('subsite_id');
      if (Settings::get('subsite_id') == 'LIOFA') {
        $this->processAliases();
      }
    }
  }

  /**
   * Delete duplicate re-directs.
   */
  protected function processAliases() {
    // Loop through all aliases and their languages on the source
    // Drupal 7 database.
    $query = $this->dbConnD7->query("SELECT PID, LANGUAGE, ALIAS FROM {url_alias}");
    $d7_aliases = $query->fetchAll();

    $path_alias_storage = $this->entityTypeManager->getStorage('path_alias');
    foreach ($d7_aliases as $row) {
      // Load the corresponding alias from the Drupal 10 database.
      $alias_objects = $path_alias_storage->loadByProperties([
        'alias' => '/' . $row->ALIAS
      ]);
      $first_alias = TRUE;
      if (count($alias_objects) > 0) {
        foreach ($alias_objects as $alias) {
          // Check the language code.
          if ($alias->langcode != $row->LANGUAGE) {
            // The language code was incorrect on Drupal 10 so update the
            // corresponding alias with the same language code.
            $this->logger->notice('Updating alias ' . $alias->id() . ', ' . $row->ALIAS . ', to ' . $row->LANGUAGE);
            $alias->langcode = $row->LANGUAGE;
            $alias->save();
          }
          if ($first_alias) {
            $first_alias = FALSE;
          }
          else {
            // We have already processed an alias with this path, so
            // we must delete duplicates.
            $this->logger->notice('Deleting alias ' . $alias->id() . ', ' . $alias->getAlias());
            $path_alias_storage->delete([$alias]);
          }
        }
      }
    }
  }

}
