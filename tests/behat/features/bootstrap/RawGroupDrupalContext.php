<?php

namespace Drupal\Tests\Behat;

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\group\Entity\Group;
use Drupal\Tests\Behat\TestContent\GroupData;
use RuntimeException;
use Drupal\Tests\Behat\GroupManager;

class RawGroupDrupalContext extends RawDrupalContext {

  /**
   * Drupal group manager.
   *
   * @var \Drupal\Tests\Behat\GroupManager
   */
  protected $groupManager;

  public function __construct() {
    $this->setGroupManager();
  }

  /**
   * Sets the Drupal group manager instance.
   */
  public function setGroupManager()
  : void {
    $this->groupManager = GroupManager::getInstance();
  }

  /**
   * Gets the Drupal group manager instance.
   *
   * @return \Drupal\Tests\Behat\GroupManager
   */
  public function getGroupManager()
  : GroupManager {
    if (NULL === $this->groupManager) {
      $this->setGroupManager();
    }

    return $this->groupManager;
  }

  /**
   * Create a group from values in the stdClass object.
   *
   * @param \stdClass $group
   *
   * @return \stdClass
   *   The created group.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function groupCreate(\stdClass $group)
  : \stdClass {
    $group = GroupData::add((array) $group);
    $group = $this->getGroupManager()->groupCreate((object) $group);
    return (object) $group;
  }

  /**
   * Creates test content of the given type with the given values. See the
   * README in tests/behat/features/bootstrap/Drupal/TestContent/
   *
   * @param string $contentType
   * @param array $values
   *
   * @return mixed
   */
  protected function contentCreate(string $contentType, array $values) {

    $class = $this->getCreateClassPath($contentType);
    $content = $class::create($values);
    if ($content !== NULL) {
      // The Drupal Extension module manages clean up of $this->nodes.
      $this->nodes[] = $content;
      return $content;
    }

    throw new RuntimeException("Failed to create content. (Type: $contentType)");
  }

  /**
   * @param string $contentType
   * @param array $values
   * @param \Drupal\group\Entity\Group|null $group
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function groupContentCreate(
    string $contentType, array $values, Group $group = NULL
  ) : void {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $content */
    $content = $this->contentCreate($contentType, $values);
    if ($group === NULL) {
      $group = Group::load($this->getGroupManager()->getCurrentGroup()->gid);
    }
    $group->addContent($content, "group_node:$contentType");
  }

  /**
   * Return a namespace path to the content creation class for the given type.
   * Ex: $type = 'basic_page', returns
   * '\Drupal\Tests\Behat\TestContent\BasicPage'
   *
   * @param $type
   *
   * @return string
   */
  private function getCreateClassPath($type)
  : string {
    $class_name = str_replace(
      ' ', '', ucwords(str_replace('_', ' ', $type))
    );
    return "\Drupal\Tests\Behat\TestContent\\$class_name";
  }

  /**
   * Create content from a table. Optionally, assigns to user and group.
   *
   * @param $contentType
   * @param \Behat\Gherkin\Node\TableNode $table
   * @param int $uid
   * @param int|null $gid
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function addContentFromTable($contentType, TableNode $table, int $uid = 1, int $gid = NULL)
  : void {
    $table_hash = $table->getHash();
    foreach ($table_hash as $node_hash) {
      foreach ($node_hash as $key => $value) {
        // This matches a number representing an array index.
        // Example $key: 'field_foo[0][assoc_property]'
        preg_match('/(.+)\[(\d)]\[(.+)]/', $key, $match);
        if (isset($match[1])) {
          $node_hash[$match[1]][(int) $match[2]][$match[3]] = $value;
          unset($node_hash[$key]);
        }
      }
      $node_hash['uid'] = $uid;

      if ($gid) {
        /** @var Group $group */
        $group = Group::load($gid);
        $this->groupContentCreate($contentType, $node_hash , $group);
      }
      else {
        $this->contentCreate($contentType, $node_hash);
      }
    }
  }

  /**
   * Find a group by the given label and return the ID.
   *
   * @param string $label
   *   The group label to look for.
   *
   * @return int|string
   *   The group ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getGroupIdByLabel(string $label) {
    $gid = NULL;
    $group_ids = \Drupal::entityTypeManager()
      ->getStorage('group')
      ->loadByProperties([
        'label' => $label,
      ]);
    if ($group_ids) {
      $gid = array_key_first($group_ids);
    }
    return $gid;
  }

  /**
   * Remove any created groups.
   *
   * @AfterScenario
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function cleanGroups()
  : void {
    if ($this->getGroupManager()->hasGroups()) {
      foreach ($this->getGroupManager()->getGroups() as $group) {
        $this->getGroupManager()->groupDelete($group);
      }
      $this->getGroupManager()->clearGroups();
    }
  }

}
