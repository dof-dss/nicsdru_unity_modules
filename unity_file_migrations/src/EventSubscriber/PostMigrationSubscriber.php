<?php

namespace Drupal\unity_file_migrations\EventSubscriber;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Site\Settings;

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
    $this->dbConnD7 =  Database::getConnection('default', 'liofa7');
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
    $this->logger->notice('Checking migration');
    if ($event_id === 'upgrade_d7_url_alias') {
      $this->logger->notice('Checking site');
      $site = Settings::get('subsite_id');
      $this->logger->notice('Site is ' . $site);
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
    $this->logger->notice('Processing aliases');
    $query = $this->dbConnD7->query("SELECT PID, LANGUAGE, ALIAS FROM {url_alias}");
    $d7_aliases = $query->fetchAll();

    foreach ($d7_aliases as $row) {
      // Update the corresponding alias on the destination (Drupal 10)
      // database with the same language code.
      $this->logger->notice('Updating alias ' . $row->PID . ', ' . $row->ALIAS . ', to ' . $row->LANGUAGE);
      $res = $this->dbConnD10->update('path_alias')
        ->fields(['langcode' => $row->LANGUAGE])
        ->condition('id', $row->PID)
        ->execute();
      $res = $this->dbConnD10->update('path_alias_revision')
        ->fields(['langcode' => $row->LANGUAGE])
        ->condition('id', $row->PID)
        ->execute();
    }
  }
}
