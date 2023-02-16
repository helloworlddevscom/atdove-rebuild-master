<?php

namespace Drupal\atdove_utilities;

use Drupal\Core\Entity\EntityInterface;

/**
 * Helper methods for getting and comparing field values.
 */
class ValueFetcher {

  /**
   * Method for getting all of the values of a field on an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that you want to get a field value from.
   * @param string $field
   *   The field on the entity you want to get a value from.
   *
   * @return array|null
   *   Returns the array of values Drupal has for the field.
   *   Returns NULL if the field is empty, or does not have the field.
   */
  public static function getAllValues(EntityInterface $entity, string $field) {
    $value = NULL;
    if ($entity->hasField($field) && $entity->get($field)->first()) {
      $value = $entity->get($field)->first()->getValue();
    }
    return $value;
  }

  /**
   * Calls a passed method on the first return on a Drupal field value.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity which you wish to access a field value upon.
   * @param string $field
   *   The field to attempt to load first and then call a method on.
   * @param string $method
   *   The method you desire to call on the returned value for first.
   *
   * @return mixed|null
   *   Returns whatever the particular method you called on first is.
   *   Returns NULL if there is no field, or no first value for field.
   */
  public static function callMethodOnFirst(EntityInterface $entity, string $field, string $method) {
    $value = NULL;
    if ($entity->hasField($field) && $entity->get($field)->first()) {
      if (method_exists($entity->get($field)->first(), $method)) {
        $value = $entity->get($field)->first()->$method();
      }
      else {
        \Drupal::logger('ValueFetcher')
          ->error('callMethodOnFirst failed as method was not present on first()');
      }
    }
    return $value;
  }

  /**
   * Compare two entities field values to see if they are identical.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity_one
   *   The first entity to check.
   * @param \Drupal\Core\Entity\EntityInterface $entity_two
   *   The second entity to check.
   * @param string $field
   *   The field to check upon.
   *
   * @return bool
   *   TRUE if the values are the same. FALSE if they are not the same.
   */
  public static function areEqual(EntityInterface $entity_one, EntityInterface $entity_two, string $field)
  : bool {
    // DO NOT CHANGE TO ABSOLUTE COMPARISON UNTIL -MAYBE- D9 NORMALIZES VALUES!
    return self::getFirstValue($entity_one, $field) == self::getFirstValue($entity_two, $field);
  }

  /**
   * Method for getting the first value of a field on an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that you want to get a field value from.
   * @param string $field
   *   The field on the entity you want to get a value from.
   *
   * @return mixed|null
   *   Returns the first field value if there is one.
   *   Returns NULL if the field is empty, or does not have the field.
   */
  public static function getFirstValue(EntityInterface $entity, string $field) {
    $value = NULL;
    if (
      $entity->hasField($field)
      && $entity->get($field)->first()
      && !empty($entity->get($field)->first()->getValue())
    ) {
      $value = array_values($entity->get($field)->first()->getValue())[0];
    }
    return $value;
  }

  /**
   * Static method to check if a field exists but has no value.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   * @param string $field
   *   The field machine name to check.
   *
   * @return bool
   *   True if field exists AND is empty. False if no field, or field has value.
   */
  public static function isEmpty(EntityInterface $entity, string $field)
  : bool {
    return $entity->hasField($field) && is_null(self::getFirstValue($entity, $field));
  }

  /**
   * Unserialize the first value of a field on an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that you want to get a field value from.
   * @param string $field
   *   The field on the entity you want to get a value from.
   *
   * @return mixed
   *   The converted field data.
   */
  public static function unserializeFirstValue(
    EntityInterface $entity,
    string $field
  ) {
    return unserialize(
      self::getFirstValue($entity, $field),
      ['allowed_classes' => [EntityInterface::class]]
    );
  }

}
