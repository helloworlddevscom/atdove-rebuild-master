<?php

namespace Drupal\atdove_migrate\Plugin\migrate\destination;

use Drupal\migrate\Row;
use Drupal\group\Entity\Group;
use Drupal\user\Plugin\migrate\destination\EntityUser;
use Drupal\Core\Entity\ContentEntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * @MigrateDestination(
 *   id = "custom_user"
 * )
 */
class EntityUserPostSave extends EntityUser {

  /**
   * The og group ids array we passed through.
   *
   * @var array
   */
  private $gids;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    // Basically we need to "trick" the plugin_id to use the right entity type.

    $plugin_id = 'entity:user';
    $entity_type = static::getEntityTypeId($plugin_id);
    return new static(
      $configuration,
      'entity:user',
      $plugin_definition,
      $migration,
      $container->get('entity_type.manager')->getStorage($entity_type),
      array_keys($container->get('entity_type.bundle.info')->getBundleInfo($entity_type)),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('password')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    // Set this so we can process in the save method.
    $this->gids = $row->getDestinationProperty('gids');
    $this->g_rids = $row->getDestinationProperty('g_rids');
    $this->roles = $row->getDestinationProperty('main_roles');
    return parent::import($row, $old_destination_id_values);
  }

  /**
   * {@inheritdoc}
   */
  protected function save(ContentEntityInterface $entity, array $old_destination_id_values = []) {
    // We need to pull the parts of the parents into this class.
    // If we don't, we get a failed migration.

    // From EntityUser::save()
    // Do not overwrite the root account password.
    if ($entity->id() != 1) {
      // Set the pre_hashed password so that the PasswordItem field does not hash
      // already hashed passwords. If the md5_passwords configuration option is
      // set we need to rehash the password and prefix with a U.
      // @see \Drupal\Core\Field\Plugin\Field\FieldType\PasswordItem::preSave()
      $entity->pass->pre_hashed = TRUE;
      if (isset($this->configuration['md5_passwords'])) {
        $entity->pass->value = 'U' . $this->password->hash($entity->pass->value);
      }
    }

    // Save the entity as in EntityContentBase::save().
    $entity->save();
    $main_roles = $this->roles;
    $role_names = [];
    $role_names_final = [];
    //We can add extra roles accoerding to their main roles. Perhaps a group "Contributor" role for anyone who has the main Contributor ROle. Just add ones that are the most common.
    // Go through Each Group and add users.
    foreach ($this->gids as $gid) {
      if ($gid !== NULL) {
        $group = Group::load($gid);
       
        if ($group !== NULL) {
         $group_type_id = $group->getGroupType()->id();  

          //Contains gids and rids
          if (!empty($this->g_rids)) {
            foreach ($this->g_rids as $rids) {
              if (isset($rids['gid'])) {
                if ($gid = $rids['gid']) {
                  $rid = $rids['rid'];
                  //Make them an admin in the group if they are a global admin
                  if (($rid == 3) || ($rid == 6) || array_key_exists(3, $main_roles) || array_key_exists(21, $main_roles)) {
                    $role_names[] = 'admin';
                  }
                  if (array_key_exists(4, $main_roles) || array_key_exists(8, $main_roles) || array_key_exists(9, $main_roles)) {
                    $role_names[] = 'contrib';
                  }
                  if (array_key_exists(6, $main_roles)) {
                    $role_names[] = 'subscriber';
                  }
                  if (array_key_exists(11, $main_roles)) {
                    $role_names[] = 'cont_admin';
                  }
                  if ($rid == 2) {
                    $role_names[] = 'member';
                  }

                  //Build whole role names array
                  foreach ($role_names as $role) {
                    $role_names_final[] = $group_type_id . '-' . $role;
                  }

                  $values = ['group_roles' => $role_names_final];

                }              
              
              }

            }
          }
          else {
            $role_names_final[] = $group_type_id . '-member';
            $values = ['group_roles' => $role_names_final];
          }

          $group->addMember($entity, $values); 
          $group->save();  

        }
      }
    }
    // Return the entity ids as in EntityContentBase::save().
    return [$entity->id()];
 
  }
}