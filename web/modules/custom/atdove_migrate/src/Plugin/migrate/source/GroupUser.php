<?php

namespace Drupal\atdove_migrate\Plugin\migrate\source;

use Drupal\user\Plugin\migrate\source\d7\User;
use Drupal\migrate\Row;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Extends the D7 Node source plugin so we can grab OG info.
 *
 * @MigrateSource(
 *   id = "d7_group_user",
 *   source_module = "user"
 * )
 */
class GroupUser extends User {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Grab our uid and grab the Group ID from the D7 OG tables.
    $uid = $row->getSourceProperty('uid');

    // Grab data from both tables.
    $query = $this->select('og_membership', 'og')
      ->fields('og', ['gid'])
      ->condition('etid', $uid)
      ->condition('entity_type', 'user')
      ->execute()
      ->fetchAll();

    $query2 = $this->select('og_users_roles', 'our')
      ->fields('our', ['gid', 'rid'])
      ->condition('uid', $uid)
      ->execute()
      ->fetchAll();
    $g_rids = $query2;
    // Set our array of values.
    $gids = [];

    foreach ($query as $gid) {
      $gids[] = $gid['gid'];
    }

    foreach ($query2 as $gid) {
      $g_rids[] = $gid['gid'];  
    }
    
    // Set the property to use for the user yaml ER field.
    $row->setSourceProperty('gids', $gids);

    // Set the property to use in the custom_user destination.
    $row->setDestinationProperty('gids', $gids);

    //Prepare Roles for migration
    $query3 = $this->select('users_roles', 'r');
    $query3->fields('r', ['rid']);
    $query3->condition('r.uid', $uid, '=');
    $record = $query3->execute()->fetchAllKeyed();
    $row->setSourceProperty('roles', array_keys($record));
    $row->setDestinationProperty('main_roles', $record);

    // Set the property to use in the custom_user destination.
    $row->setSourceProperty('g_rids', $g_rids);
    $row->setDestinationProperty('g_rids', $g_rids);

    // Set Field Collection field_org_employee_ids field
    $query_emp_item = $this->select('field_data_field_org_employee_ids', 'fdfoei');
    $query_emp_item->condition('fdfoei.entity_id', $uid, '=');
    $query_emp_item->fields('fdfoei', ['field_org_employee_ids_value']);
    $result_query_emp_item = $query_emp_item->execute()->fetchField();

    // Now get field collection values
    $query_emp = $this->select('field_data_field_employee_id', 'fdfei');
    $query_emp->condition('fdfei.entity_id', $result_query_emp_item, '=');
    $query_emp->fields('fdfei', ['field_employee_id_value']);
    $result_query_emp = $query_emp->execute()->fetchField();

    $query_emp_id = $this->select('field_data_field_org_id_for_employee_id', 'fdfoid');
    $query_emp_id->condition('fdfoid.entity_id', $result_query_emp_item, '=');
    $query_emp_id->fields('fdfoid', ['field_org_id_for_employee_id_value']);
    $result_query_emp_id = $query_emp_id->execute()->fetchField();

    $field_org_employee_ids_new =  [
      'field_employee_id' => $result_query_emp,
      'field_org_id_for_employee_id' => $result_query_emp_id, 
    ];

    $row->setSourceProperty('field_org_employee_ids_new', $field_org_employee_ids_new);
    $row->setSourceProperty('uid', $uid);

    $u_img_fid = $this->select('users', 'u');
    $u_img_fid->condition('u.uid', $uid, '=');
    $u_img_fid->fields('u', ['picture']);
    $user_fid = $u_img_fid->execute()->fetchField();
    $u_query_img = $this->select('file_managed', 'fm');
    $u_query_img->condition('fm.fid', $user_fid, '=');
    $u_query_img->fields('fm', ['uri']);
    $field_user_image_url = $u_query_img->execute()->fetchField();
    $u_title = substr($field_user_image_url, strrpos($field_user_image_url, '/' )+1);
    $u_filename = str_replace(' ', '-', $u_title);
    $u_url = str_replace('public://', '', $field_user_image_url);
    $u_encoded_url = str_replace(' ', '%20', $u_url);
    $user_image = [
      'alt' => $u_title,
      'title' => $u_title,
      'source_path' => 'https://www.atdove.org/sites/default/files/styles/profile_photo/public/pictures/' . $u_filename,
      'file_name' => $u_filename,
    ];
    $row->setSourceProperty('user_image', $user_image);

    //negate public profile condition
    $pub = $row->getSourceProperty('field_public_profile');
    if ($pub == 1) {
      $priv = 0;
    }
    else {
      $priv = 1;
    }

    $row->setSourceProperty('field_private_profile', $priv);

    // Set created value if user is a contributor
    $query_contr = $this->select('field_data_field_contributors', 'fdfc');
    $query_contr->condition('fdfc.field_contributors_target_id', $uid, '=');
    $query_contr->fields('fdfc', ['entity_id']);
    $result_query_contr = $query_contr->execute()->fetchField();

    if (!empty($result_query_contr)) {
      $current_timestamp = new DrupalDateTime();
      $current_timestamp->getTimestamp();
      $created = strtotime($current_timestamp);
      $row->setSourceProperty('created', $created);
    }

    return parent::prepareRow($row);
  }
}