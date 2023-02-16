<?php

namespace Drupal\Tests\Behat;

use Drupal\group\Entity\Group;

/**
 *
 */
class GroupManager {

  /**
   * @var self|null
   */
  private static ?GroupManager $instance = NULL;

  /**
   * The group object representing the current group.
   *
   * @var object|null
   */
  protected ?\stdClass $group = NULL;

  /**
   * An array of group objects representing groups created during the test.
   *
   * @var \stdClass[]
   */
  protected array $groups = [];

  /**
   * @return \Drupal\Tests\Behat\GroupManager
   */
  public static function getInstance()
  : GroupManager {
    if (self::$instance === NULL) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * Returns the current group.
   *
   * @return object|null
   *   The group object.
   */
  public function getCurrentGroup()
  : ?\stdClass {
    return $this->group ?? NULL;
  }

  /**
   * Sets the current group.
   *
   * @param object $group
   *   The group object.
   */
  public function setCurrentGroup(\stdClass $group)
  : void {
    $this->group = $group;
  }

  /**
   * Create a group.
   *
   * @param object $group
   *
   * @return object
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function groupCreate(\stdClass $group)
  : \stdClass {
    /** @var \Drupal\group\Entity\Group $entity */
    $entity = Group::create((array) $group);
    $entity->save();

    // Store GID and track it for deletion.
    $group->gid = $entity->id();
    $this->addGroup($group);

    // Set current group if it isn't set already.
    if (NULL === $this->getCurrentGroup()) {
      $this->setCurrentGroup($group);
    }

    return $group;
  }

  /**
   * Adds a new group.
   *
   * Call this after creating a new group to keep track of all the groups that are
   * created in a test scenario. They can then be cleaned up after completing
   * the test.
   *
   * @param \stdClass
   *   The group object.
   */
  public function addGroup($group)
  : void {
    $this->groups[$this->convertName($group->label)] = $group;
  }

  /**
   * Returns the list of groups that were created in the test.
   *
   * @return \stdClass[]
   *   An array of group objects.
   */
  public function getGroups()
  : array {
    return $this->groups;
  }

  /**
   * Returns the group with the given group name.
   *
   * @param string $groupName
   *   The name of the group to return.
   *
   * @return object
   *   The group object.
   *
   * @throws \RuntimeException Thrown when the group with the given name does not exist.
   */
  public function getGroup(string $groupName)
  : \stdClass {
    $machineName = $this->convertName($groupName);
    if (!isset($this->groups[$machineName])) {
      throw new \RuntimeException("No group with '$groupName' name is registered with the group manager.");
    }
    return $this->groups[$machineName];
  }

  /**
   * Clears the list of groups that were created in the test.
   */
  public function clearGroups()
  : void {
    unset($this->group);
    $this->groups = [];
  }

  /**
   * Returns whether any groups were created in the test.
   *
   * @return bool
   *   TRUE if any groups are tracked, FALSE if not.
   */
  public function hasGroups()
  : bool {
    return !empty($this->groups);
  }

  /**
   * Convert group name to machine name.
   *
   * @param string $groupName
   *   The name of the group.
   *
   * @return string
   *   The group name changed to a machine name.
   */
  public function convertName(string $groupName)
  : string {
    return preg_replace('@[^a-z0-9_]+@', '_', strtolower($groupName));
  }

  /**
   * Delete a group.
   *
   * @param object $group
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function groupDelete(\stdClass $group)
  : void {
    \Drupal::entityTypeManager()
      ->getStorage('group')
      ->resetCache([
        $group->gid,
      ]);

    $group_to_delete = Group::load($group->gid);

    // Check that the group even loaded. Something else could have deleted it first.
    if($group_to_delete) {
      $group_to_delete->delete();
    }
  }

}
