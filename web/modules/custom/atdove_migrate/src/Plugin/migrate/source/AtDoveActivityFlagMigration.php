<?php

namespace Drupal\atdove_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Minimalistic example for a SqlBase source plugin.
 *
 * @MigrateSource(
 *   id = "atdove_activity_flag_migration",
 *   source_module = "atdove_migrate",
 * )
 */


class AtDoveActivityFlagMigration extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {

    $query = $this->select('flagging', 'f');
    $query->join('node', 'n', 'n.nid = f.entity_id');
    $group = $query->orConditionGroup()
      ->condition('n.type', 'article')
      ->condition('n.type', 'video');
    $query->fields('f', [
          'flagging_id',
          'fid',
          'entity_type',
          'entity_id',
          'uid',
          'sid',
          'timestamp',
        ]);
    return $query->condition($group);

  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'flagging_id' => $this->t('Flag content id'),
      'fid' => $this->t('Flag lists id #'),
      'entity_type' => $this->t('Entity type'),
      'entity_id' => $this->t('Entity #'),
      'uid' => $this->t('Owner'),
      'sid' => $this->t('Sid'),
      'timestamp' => $this->t('Timestamp'),
      'global' => $this->t('Global'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'flagging_id' => [
        'type' => 'integer',
        'alias' => 'f',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {

    // $messenger = \Drupal::messenger();
    // $logger = \Drupal::logger('flag_lists');

    // // Check if the entity exists.
    // $entity_id = $row->getSourceProperty('entity_id');

    // $entity = \Drupal::entityTypeManager()->getStorage($row->getSourceProperty('entity_type'))->load($entity_id);
    // if (empty($entity)) {
    //   $message = 'The entity with ID wasnt found';
    //   $messenger->addError($message);
    //   $logger->error($message);
    // }

     return parent::prepareRow($row);
  }

}
