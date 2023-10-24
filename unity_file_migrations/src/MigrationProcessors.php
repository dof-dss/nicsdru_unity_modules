<?php

namespace Drupal\unity_file_migrations;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drush\Commands\DrushCommands;

/**
 * A collection of methods for processing migrations.
 *
 * @package Drupal\unity_file_migrations
 */
class MigrationProcessors extends DrushCommands {

  /**
   * Node Storage definition.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * Migration database connection (Drupal 7).
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $dbConnMigrate;

  /**
   * Drupal 8 database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $dbConnDrupal8;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->nodeStorage = $entity_type_manager->getStorage('node');
    $this->dbConnMigrate = Database::getConnection('default', 'migrate');
    $this->dbConnDrupal8 = Database::getConnection('default', 'default');
  }

  /**
   * Drush command to publish Unity nodes after migration.
   *
   * @command post-migrate-publish
   */
  public function updatePublishStatus($node_type = NULL) {
    // This update should be run from drush after ALL node
    // and revision migrations have completed. Note that this process
    // will correctly set current revision and publish status for all
    // nodes but it will create new revisions. This means that once this
    // has been run there should be no more 'top up' migrations, the only
    // option is to roll back all revision and node migrations and start
    // from scratch.

    $this->output()->writeln('Sync node publish status values after migration');

    // Find all node ids in the D8 site so we know what to look for.
    $d8_nids = [];
    if ($node_type) {
      $query = $this->dbConnDrupal8->query("SELECT nid FROM {node} WHERE type = :node_type ORDER BY nid ASC", [':node_type' => $node_type]);
      $d8_nids = $query->fetchAllAssoc('nid');
    }
    else {
      $query = $this->dbConnDrupal8->query("SELECT nid FROM {node} ORDER BY nid ASC");
      $d8_nids = $query->fetchAllAssoc('nid');
    }

    // Load source node publish status fields.
    $query = $this->dbConnMigrate->query("
      SELECT nid, status FROM {node}
      WHERE nid IN (:nids[])
      ORDER BY nid ASC
    ", [':nids[]' => array_keys($d8_nids)]);
    $migrate_nid_status = $query->fetchAll();

    // Sync our D8 node publish values and revisions with those from D7.
    foreach ($migrate_nid_status as $row) {
      $this->processNodeStatus($row->nid, $row->status);
    }

    $this->output()->writeln('Updated revisions on ' . count($migrate_nid_status) . ' nodes.');
    $this->output()->writeln('Clearing all caches...');
    drupal_flush_all_caches();
  }

  /**
   * Updates the status and revisions for the specified node.
   *
   * @param int $nid
   *   The node id.
   * @param string $status
   *   The status of the node.
   */
  public function processNodeStatus(int $nid, string $status) {
    // Need to fetch the D8 revision ID for any node as it doesn't
    // always match the source db.
    $d8_vid = $this->dbConnDrupal8->query(
      "SELECT vid FROM {node_field_data} WHERE nid = :nid", [':nid' => $nid]
    )->fetchField();

    // Get the D7 revision id.
    $vid = $this->dbConnMigrate->query(
      "SELECT vid FROM {node} WHERE nid = :nid", [':nid' => $nid]
    )->fetchField();

    // Does the D7 revision exist in D8?
    $check_vid = $this->dbConnDrupal8->query(
      "SELECT vid FROM {node_field_revision} WHERE nid = :nid AND vid = :vid",
      [':nid' => $nid, ':vid' => $vid]
    )->fetchField();
    if (empty($check_vid)) {
      // D7 revision does not exist in D8, use the D8 one.
      $vid = $d8_vid;
    }

    // Make the revision current and publish if necessary.
    // @phpstan-ignore-next-line
    $revision = $this->nodeStorage->loadRevision($vid);
    if (!empty($revision)) {
      /** @var \Drupal\Core\Entity\EditorialContentEntityBase $revision */
      $revision->isDefaultRevision(TRUE);

      if ($status == 1) {
        $revision->setPublished();
      }
      $revision->save();
    }

    // Publish node if necessary.
    if ($status == 1) {
      // If node was published on D7, make sure that it is published on D8.
      $node = $this->nodeStorage->load($nid);
      if ($node instanceof NodeInterface) {
        $node->status = 1;
        $node->set('moderation_state', 'published');
        $node->save();
      }
    }
    else {
      // See if the moderation state on D7 was 'needs review'.
      $moderation_status = $this->dbConnMigrate->query("
        select state from {workbench_moderation_node_history}
        where hid = (select max(hid) from {workbench_moderation_node_history} where nid = :nid)
          ", [':nid' => $nid])->fetchField();

      if ($moderation_status == 'needs_review') {
        // Make sure state is 'needs review' on D8.
        $node = $this->nodeStorage->load($nid);
        /** @var \Drupal\node\NodeInterface $node */
        $node->set('moderation_state', 'needs_review');
        $node->save();
      }
    }
  }

}
