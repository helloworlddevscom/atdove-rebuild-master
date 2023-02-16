<?php
/**
 * @file
 * Contains \Drupal\atdove_migrate\Plugin\migrate\source\Node.
 */
 
namespace Drupal\atdove_migrate\Plugin\migrate\source;
 
use Drupal\migrate\Row;
use Drupal\node\Plugin\migrate\source\d7\Node as D7Node;

/**
 * Custom node source including url aliases.
 * This plugin is for Organic Groups in D7.
 *
 * @MigrateSource(
 *   id = "atdove_migrate_group"
 * )
 */
class AtDoveGroup extends D7Node {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return ['alias' => $this->t('Path alias')] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Include path alias.
    $nid = $row->getSourceProperty('nid');
    $node_type = $row->getSourceProperty('type');

    $query = $this->select('url_alias', 'ua')
      ->fields('ua', ['alias']);
    $query->condition('ua.source', 'node/' . $nid);
    $alias = $query->execute()->fetchField();
    if (!empty($alias)) {
      $row->setSourceProperty('alias', '/' . $alias);
    }

    // Grab Related Group ID from the D7 OG tables.
    $query2 = $this->select('og_membership', 'og')
      ->fields('og', ['gid'])
      ->condition('etid', $nid)
      ->condition('entity_type', 'node')
      ->execute()
      ->fetchAll();

    // Set our array of values.
    $gids = [];
    foreach ($query2 as $gid) {
      $gids[] = $gid['gid'];
    }

    $row->setSourceProperty('user_id', '0');
    $row->setSourceProperty('uid', '0');
    // Set the property to use for the user yaml ER field.
    $row->setSourceProperty('gids', $gids);


      if ($node_type == 'organization') {
        $c_img_fid = $this->select('field_data_field_clinic_logo', 'fii');
        $c_img_fid->condition('fii.entity_id', $nid, '=');
        $c_img_fid->fields('fii', ['field_clinic_logo_fid']);
        $c_fid = $c_img_fid->execute()->fetchField();
        $c_query_img = $this->select('file_managed', 'fm');
        $c_query_img->condition('fm.fid', $user_fid, '=');
        $c_query_img->fields('fm', ['uri']);
        $c_image_url = $c_query_img->execute()->fetchField();
        
        $c_title = substr($c_image_url, strrpos($c_image_url, '/' )+1);
        $c_filename = str_replace(' ', '-', $c_title);
        $c_url = str_replace('public://', '', $c_image_url);
        $c_encoded_url = str_replace(' ', '%20', $c_url);
        $clinic_image = [
          'alt' => $c_title,
          'title' => $c_title,
          'source_path' => 'https://www.atdove.org/sites/atdove.org/files/' . $u_encoded_url,
          'file_name' => $c_filename,
        ];
        $row->setSourceProperty('field_clinic_logo', $clinic_image);

        // // File field
        // $org_fid = $this->select('field_data_field_import_team_file', 'fitf');
        // $org_fid->condition('fitf.entity_id', $uid, '=');
        // $org_fid->fields('fitf', ['field_import_team_file_fid']);
        // $org_fid = $org_fid->execute()->fetchField();
        // $org_query = $this->select('file_managed', 'fm');
        // $org_query->condition('fm.fid', $org_fid, '=');
        // $org_query->fields('fm', ['uri']);
        // $field_org_url = $org_query->execute()->fetchField();
        
        // $org_title = substr($field_org_url, strrpos($field_org_url, '/' )+1);
        // $org_filename = str_replace(' ', '-', $org_title);
        // $org_url = str_replace('public://', '', $field_org_url);
        // $org_encoded_url = str_replace(' ', '%20', $org_url);
        // $org_file = [
        //   'source_path' => 'https://www.atdove.org/sites/atdove.org/files/' . $org_encoded_url,
        //   'file_name' => $org_filename,
        // ];

        // $row->setSourceProperty('field_import_team_file', $org_file);


      }

      if ($migration_id == 'atdove_learning_paths') {
      // Get main article image
      $a_img_fid = $this->select('field_data_field_image', 'fdfi');
      $a_img_fid->condition('fdfi.entity_id', $nid, '=');
      $a_img_fid->fields('fdfi', ['field_image_fid']);
      $article_fid = $a_img_fid->execute()->fetchField();
      $a_query_img = $this->select('file_managed', 'fm');
      $a_query_img->condition('fm.fid', $a_img_fid, '=');
      $a_query_img->fields('fm', ['uri']);
      $field_article_image_url = $a_query_img->execute()->fetchField();
      //print($field_article_image_url);
      $a_title = substr($field_article_image_url, strrpos($field_article_image_url, '/' )+1);
   
      $a_filename = str_replace(' ', '-', $a_title);
      $a_url = str_replace('public://', '', $field_article_image_url);
      //print(pathinfo($a_url));
      $a_path = pathinfo($a_url);
      //print_r($a_path['dirname']);
  
      $a_encoded_url = str_replace(' ', '%20', $a_url);
      $article_image = [
        'alt' => $a_title,
        'title' => $a_title,
        'source_path' => 'https://www.atdove.org/sites/atdove.org/files/' . $a_encoded_url,
        'file_name' => $a_filename,
        'dir' => $a_path['dirname'],
      ];

      $row->setSourceProperty('tp_image', $article_image);

      }

    return parent::prepareRow($row);
  }

}
