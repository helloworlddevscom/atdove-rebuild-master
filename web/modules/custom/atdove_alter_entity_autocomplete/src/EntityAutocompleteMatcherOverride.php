<?php

namespace Drupal\atdove_alter_entity_autocomplete;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Tags;

class EntityAutocompleteMatcherOverride extends \Drupal\Core\Entity\EntityAutocompleteMatcher
{

  /**
   * Overrides user entity autocomplete suggestion list on the group add member page to
   * load only users of the selected group -- see JIRA ticket AR-589.
   *
   * @param $target_type
   * @param $selection_handler
   * @param $selection_settings
   * @param $string
   * @return array
   */
  public function getMatches($target_type, $selection_handler, $selection_settings, $string = '')
  {

    $matches = [];

    $options = [
      'target_type' => $target_type,
      'handler' => $selection_handler,
      'handler_settings' => $selection_settings,
    ];

    $handler = $this->selectionManager->getInstance($options);

    if (isset($string)) {
      // Get an array of matching entities.
      $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
      $entity_labels = $handler->getReferenceableEntities($string, $match_operator, 10);

      // Loop through the entities and convert them into autocomplete output.
      foreach ($entity_labels as $values) {
        foreach ($values as $entity_id => $label) {
          $key = "$label ($entity_id)";
          $label = "$label ($entity_id)";
          // Strip things like starting/trailing white spaces, line breaks and
          // tags.
          $key = preg_replace('/\s\s+/', ' ', str_replace("\n", '', trim(Html::decodeEntities(strip_tags($key)))));
          // Names containing commas or quotes must be wrapped in quotes.
          $key = Tags::encode($key);
          $matches[] = ['value' => $key, 'label' => $label];
        }
      }

      // Only run if the query is coming from the group add members page
      $request = \Drupal::request();
      $referer = $request->headers->get('referer');
      if (stripos($referer, "/content/add/group_membership?destination=/group/") === false) {
        return $matches;
      }
    }

    // No group info is passed to getMatches(), so picking the group id off of the http referer url (the group add member page url).
    $groupId = explode("/", $referer)['4'];

    // Grabbing any parent group(s).  We want to make parent group users available to subgroups instead of all users of the application.
    $groupHierarchyManager = \Drupal::service('ggroup.group_hierarchy_manager');
    $parentGroups = $groupHierarchyManager->getGroupSupergroups($groupId);

    $parentGroupIdsArray = [];
    $parentGroupIds = null;
    foreach($parentGroups as $pg) {
      $parentGroupIdsArray[] = $pg->id();
    }
    if(count($parentGroupIdsArray)) {
      $parentGroupIds = implode(',', $parentGroupIdsArray);
    }

    // Grab all users from parent group(s)
    $database = \Drupal::database();
    $sql = $this->constructQuery($parentGroupIds, $groupId, $string);
    $query = $database->query($sql);
    $results = $query->fetchAll();

    // prepare parent group(s) user matches
    $parentGroupUserMatches = [];
    foreach($results as $result) {
      $parentGroupUserMatches[] = [
        "value" => $result->user,
        "label" => $result->user
      ];
    }

    return $parentGroupUserMatches;
  }

  /**
   * @param $parentGroupIds
   * @param $groupId
   * @param $string
   * @return string
   */
  // TODO: Might be better to refactor via a query builder/dynamic query at some point
  private function constructQuery($parentGroupIds, $groupId, $string) {
    $sql = "SELECT CONCAT(uf.field_first_name_value, ' ', ul.field_last_name_value, ' (', u.uid ,')') AS 'user' ";
    $sql .= "FROM users AS u ";
    $sql .= "JOIN users_field_data AS ud ON ud.uid = u.uid ";
    $sql .= "JOIN user__field_first_name AS uf ON uf.entity_id = u.uid ";
    $sql .= "JOIN user__field_last_name AS ul ON ul.entity_id = u.uid ";
    $sql .= "JOIN user__field_user_organization AS uo ON uo.entity_id = u.uid ";
    // query by parent group id(s) if we have a parent group, otherwise query by the group id as it's a parent level group.
    if(!is_null($parentGroupIds)) {
      $sql.= "WHERE uo.field_user_organization_target_id IN({$parentGroupIds}) AND (ud.name LIKE '%{$string}%' OR ud.mail LIKE '%{$string}%' ";
    } else {
      $sql.= "WHERE uo.field_user_organization_target_id = {$groupId} AND (ud.name LIKE '\%{$string}%' OR ud.mail LIKE '%{$string}%' ";
    }
    $sql .= "OR CONCAT(uf.field_first_name_value, ' ', ul.field_last_name_value) LIKE '%{$string}%') ";
    $sql .= "GROUP BY u.uid LIMIT 10 OFFSET 0";
    return $sql;
  }

}
