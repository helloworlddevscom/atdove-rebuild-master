<?php

namespace Drupal\atdove_migrate\Plugin\migrate\source;

use Drupal\node\Plugin\migrate\source\d7\Node;
use Drupal\migrate\Row;
use Drupal\user\Entity\User;

/**
 * Extends the D7 Node source plugin so we can grab OG info.
 *
 * @MigrateSource(
 *   id = "d7_node_atdove",
 *   source_module = "node"
 * )
 */
class AtDoveNode extends Node {

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
    // Grab our nid and grab the Group ID from the D7 OG table.
    $nid = $row->getSourceProperty('nid');
    $node_type = $row->getSourceProperty('type');

    $query = $this->select('og_membership', 'og')
      ->fields('og', ['gid'])
      ->condition('etid', $nid)
      ->condition('entity_type', 'node')
      ->execute()
      ->fetchAll();

    // Set our array of values.
    $gids = [];
    foreach ($query as $gid) {
      $gids[] = $gid['gid'];
    }
    
    // Set the property to use as source in the yaml.
    $row->setSourceProperty('gids', $gids);
    // Get Field Collection item id from db; field isn't working.

    $query_acc_item = $this->select('field_data_field_accreditation_info', 'fdfai');
    $query_acc_item->condition('fdfai.entity_id', $nid, '=');
    $query_acc_item->fields('fdfai', ['field_accreditation_info_value']);
    $result_query_acc_item = $query_acc_item->execute()->fetchField();

    // Now get field collection values
    $query_acc = $this->select('field_data_field_accreditations', 'fdf');
    $query_acc->condition('fdf.entity_id', $result_query_acc_item, '=');
    $query_acc->fields('fdf', ['field_accreditations_tid']);
    $result_query_acc = $query_acc->execute()->fetchField();

    $query_acc_id = $this->select('field_data_field_accreditation_id', 'fdfid');
    $query_acc_id->condition('fdfid.entity_id', $result_query_acc_item, '=');
    $query_acc_id->fields('fdfid', ['field_accreditation_id_value']);
    $result_query_acc_id = $query_acc_id->execute()->fetchField();

    $field_accreditation_info_new =  [
      'accreditation' => $result_query_acc,
      'accreditation_id' => $result_query_acc_id, 
    ];

    $row->setSourceProperty('field_accreditation_info_new', $field_accreditation_info_new);


    // Combine related FAQ and help-topics
    $faqs = $row->getSourceProperty('field_related_faqs');
    $helptopics = $row->getSourceProperty('field_related_help_topics');
    $field_related_faqs_new = $faqs + $helptopics;
    $row->setSourceProperty('field_related_faqs_new', $field_related_faqs_new);

    // Include path alias.
    $nid = $row->getSourceProperty('nid');

    $query = $this->select('url_alias', 'ua')
      ->fields('ua', ['alias']);
    $query->condition('ua.source', 'node/' . $nid);
    $alias = $query->execute()->fetchField();
    if (!empty($alias)) {
      $row->setSourceProperty('alias', '/' . $alias);
    }

    if ($node_type == 'article' || $node_type == 'blog') {
      $article_body = $row->getSourceProperty('body');
unset($body_summary_value);
      //Get Summary Field (this is not working for sumamry fields with the "full_html_no_wysywig" format):
      $body_summary = $this->select('field_data_body', 'fdbs');
      $body_summary->condition('fdbs.entity_id', $nid, '=');
      $body_summary->fields('fdbs', ['body_summary']);
      $body_summary_value = $body_summary->execute()->fetchField();

$body_summary_value = str_replace("\xc2\xa0", ' ', $body_summary_value);

$body_summary_value = str_replace("&nbsp;", ' ', $body_summary_value);
$chars = array("\r\n", '\\n', '\\r', "\n", "\r", "\0", "\x0B");
$body_summary_value = str_replace($chars, ' ', $body_summary_value);

$table = 'opigno_activity__field_article_body';
// \Drupal::database()->update($table)
//   ->fields(array('field_article_body_summary' => $body_summary_value))
//   ->condition('entity_id', $nid)
//   ->execute();
    
      
      $row->setSourceProperty('d7_summary', $body_summary_value);

      $body_main = $this->select('field_data_body', 'fdb');
      $body_main->condition('fdb.entity_id', $nid, '=');
      $body_main->fields('fdb', ['body_value']);
      $body_value = $body_main->execute()->fetchField();

      $row->setSourceProperty('d7_body', $body_value);

      unset($body_summary);
      

      // Get FIDs of field_insert_image
      $img_fid = $this->select('field_data_field_insert_image', 'fdii');
      $img_fid->condition('fdii.entity_id', $nid, '=');
      $img_fid->fields('fdii', ['field_insert_image_fid']);
      $img_fids = $img_fid->execute()->fetchCol();

      foreach ($img_fids as $key => $fid) {
        $query_img = $this->select('file_managed', 'fm');
        $query_img->condition('fm.fid', $fid, '=');
        $query_img->fields('fm', ['uri']);
        $field_insert_image_url = $query_img->execute()->fetchField();
        $title = substr($field_insert_image_url, strrpos($field_insert_image_url, '/' )+1);
        $filename = str_replace(' ', '-', $title);
        $url = str_replace('public://', '', $field_insert_image_url);
        $dir = $path['dirname'];

        if ($node_type == 'blog') {
          $url = str_replace('http://atdove.org', '', $url);
          $dir = str_replace('http://atdove.org', '', $dir);
        }

        $encoded_url = str_replace(' ', '%20', $url);
        $path = pathinfo($url);

        $images[$key] = [
          'alt' => $title,
          'title' => $title,
          'source_path' => 'https://www.atdove.org/sites/atdove.org/files/' . $encoded_url,
          'file_name' => $filename,
          'dir' => $dir,
        ];
      }

      // Get main article image
      $a_img_fid = $this->select('field_data_field_image', 'fdfi');
      $a_img_fid->condition('fdfi.entity_id', $nid, '=');
      $a_img_fid->fields('fdfi', ['field_image_fid']);
      $article_fid = $a_img_fid->execute()->fetchField();
      $a_query_img = $this->select('file_managed', 'fm');
      $a_query_img->condition('fm.fid', $a_img_fid, '=');
      $a_query_img->fields('fm', ['uri']);
      $field_article_image_url = $a_query_img->execute()->fetchField();
      $a_title = substr($field_article_image_url, strrpos($field_article_image_url, '/' )+1);
      $a_filename = str_replace(' ', '-', $a_title);
      $a_url = str_replace('public://', '', $field_article_image_url);
      $a_path = pathinfo($a_url);
      $a_encoded_url = str_replace(' ', '%20', $a_url);
      $article_image = [
        'alt' => $a_title,
        'title' => $a_title,
        'source_path' => 'https://www.atdove.org/sites/atdove.org/files/' . $a_encoded_url,
        'file_name' => $a_filename,
        'dir' => $a_path['dirname'],
      ];

      $row->setSourceProperty('images', $images);
      $row->setSourceProperty('article_image', $article_image);

    }
    if ($node_type == 'guides') {
      // Get FIDs of field_insert_image
      $img_fid = $this->select('field_data_field_insert_image', 'fii');
      $img_fid->condition('fii.entity_id', $nid, '=');
      $img_fid->fields('fii', ['field_insert_image_fid']);
      $img_fids = $img_fid->execute()->fetchCol();
 
      foreach ($img_fids as $key => $fid) {
        $query_img = $this->select('file_managed', 'fm');
        $query_img->condition('fm.fid', $fid, '=');
        $query_img->fields('fm', ['uri']);
        $field_insert_image_url = $query_img->execute()->fetchField();
        $title = substr($field_insert_image_url, strrpos($field_insert_image_url, '/' )+1);
        $filename = str_replace(' ', '-', $title);
        $url = str_replace('public://', '', $field_insert_image_url);
        $encoded_url = str_replace(' ', '%20', $url);
        $images[$key] = [
          'alt' => $title,
          'title' => $title,
          'source_path' => 'https://www.atdove.org/sites/atdove.org/files/' . $encoded_url,
          'file_name' => $filename,
        ];
      }

      $row->setSourceProperty('images', $images);

    }

    if ($node_type == 'video') {

      // Get thumbnail video image
      $a_img_fid = $this->select('field_data_field_image', 'fii');
      $a_img_fid->condition('fii.entity_id', $nid, '=');
      $a_img_fid->fields('fii', ['field_image_fid']);
      $thumbnail_fid = $a_img_fid->execute()->fetchField();
      $a_query_img = $this->select('file_managed', 'fm');
      $a_query_img->condition('fm.fid', $thumbnail_fid, '=');
      $a_query_img->fields('fm', ['uri']);

      $field_thumbnail_image_url = $a_query_img->execute()->fetchField();

      $a_title = substr($field_thumbnail_image_url, strrpos($field_thumbnail_image_url, '/' )+1);
      $a_filename = str_replace(' ', '-', $a_title);
      $a_url = str_replace('public://', '', $field_thumbnail_image_url);
      $a_encoded_url = str_replace(' ', '%20', $a_url);
  
      $thumbnail_image = [
        'alt' => $a_title,
        'title' => $a_title,
        'source_path' => 'https://www.atdove.org/sites/atdove.org/files/' . $a_encoded_url,
        'file_name' => $a_filename,
      ];

      $row->setSourceProperty('thumbnail_image', $thumbnail_image);

      // Get featured video image
      $f_img_fid = $this->select('field_data_field_featured_image', 'ffii');
      $f_img_fid->condition('ffii.entity_id', $nid, '=');
      $f_img_fid->fields('ffii', ['field_featured_image_fid']);
      $f_fid = $f_img_fid->execute()->fetchField();
      $f_query_img = $this->select('file_managed', 'fmf');
      $f_query_img->condition('fmf.fid', $f_fid, '=');
      $f_query_img->fields('fmf', ['uri']);
      $field_f_image_url = $f_query_img->execute()->fetchField();
      $f_title = substr($field_f_image_url, strrpos($field_f_image_url, '/' )+1);
      $f_filename = str_replace(' ', '-', $f_title);
      $f_url = str_replace('public://', '', $field_f_image_url);
      $f_encoded_url = str_replace(' ', '%20', $f_url);
      $f_image = [
        'alt' => $f_title,
        'title' => $f_title,
        'source_path' => 'https://www.atdove.org/sites/atdove.org/files/' . $f_encoded_url,
        'file_name' => $f_filename,
      ];

      $row->setSourceProperty('featured_image', $f_image);

      // Get FIDs of field_insert_image
      $img_fid = $this->select('field_data_field_insert_image', 'fdii');
      $img_fid->condition('fdii.entity_id', $nid, '=');
      $img_fid->fields('fdii', ['field_insert_image_fid']);
      $img_fids = $img_fid->execute()->fetchCol();

      foreach ($img_fids as $key => $fid) {
        $query_img = $this->select('file_managed', 'fmi');
        $query_img->condition('fmi.fid', $fid, '=');
        $query_img->fields('fmi', ['uri']);
        $field_insert_image_url = $query_img->execute()->fetchField();
        $title = substr($field_insert_image_url, strrpos($field_insert_image_url, '/' )+1);
        $filename = str_replace(' ', '-', $title);
        $url = str_replace('public://', '', $field_insert_image_url);
        $encoded_url = str_replace(' ', '%20', $url);
        $images[$key] = [
          'alt' => $title,
          'title' => $title,
          'source_path' => 'https://www.atdove.org/sites/atdove.org/files/' . $encoded_url,
          'file_name' => $filename,
        ];
      }

      $row->setSourceProperty('images', $images);

      // Get Wistia Key
      $wist = $this->select('field_data_field_wistia_iframe_logged_in_ac', 'wist');
      $wist->condition('wist.entity_id', $nid, '=');
      $wist->fields('wist', ['field_wistia_iframe_logged_in_ac_value']);
      $wistia_long = $wist->execute()->fetchField();
      $wistia_new_url = '';
      if (strpos($wistia_long, 'wistia_async')) {
        $arr = explode('wistia_async_', $wistia_long);
        $arr2 = explode(' ', $arr[1]);
        $wistia_id = $arr2[0];
        $wistia_id = str_replace('"', "", $wistia_id);
        $wistia_new_url = 'https://fast.wistia.com/medias/' . $wistia_id;
      }
      $row->setSourceProperty('field_wistia_video', $wistia_new_url);
    }

    if ($node_type == 'assignment') {
      $uid = $row->getSourceProperty('uid');
      if ($uid == 0) {
        $row->setSourceProperty('node_uid', 1);
      }
      else {
      $row->setSourceProperty('node_uid', $uid);
      }

      $query_field_assignee = $this->select('field_data_field_assignee', 'fdfass');
      $query_field_assignee->condition('fdfass.entity_id', $nid, '=');
      $query_field_assignee->fields('fdfass', ['field_assignee_target_id']);
      $result_field_assignee = $query_field_assignee->execute()->fetchField();

      if (empty($result_field_assignee) || (!User::load($result_field_assignee))) {
        $row->setSourceProperty('new_field_assignee', 0); 
      }
      else {
        $row->setSourceProperty('new_field_assignee', $result_field_assignee); 
      }

    }

    return parent::prepareRow($row);
  }
}