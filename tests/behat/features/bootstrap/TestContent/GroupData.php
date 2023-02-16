<?php

namespace Drupal\Tests\Behat\TestContent;

class GroupData {

  /**
   * @param array $values
   *
   * @return array
   */
  public static function add(array $values = [])
  : array {

    $arr = [
      'type' => 'organization',
      'label' => 'Behat Test Group',
      'field_group_header_title' => 'Behat Test Group',
      'uid' => 1,
    ];

    $data = array_replace_recursive($arr, $values);

    return $data;
  }

}
