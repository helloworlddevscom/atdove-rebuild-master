<?php

namespace Drupal\atdove_alter_entity_autocomplete\Controller;

use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\atdove_alter_entity_autocomplete\EntityAutocompleteMatcherOverride;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EntityAutocompleteController extends \Drupal\system\Controller\EntityAutocompleteController {

  /**
   * The autocomplete matcher for entity references.
   */
  protected $matcher;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityAutocompleteMatcherOverride $matcher, KeyValueStoreInterface $key_value) {
    $this->matcher = $matcher;
    $this->keyValue = $key_value;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('atdove_alter_entity_autocomplete.autocomplete_matcher'),
      $container->get('keyvalue')->get('entity_autocomplete')
    );
  }

}
