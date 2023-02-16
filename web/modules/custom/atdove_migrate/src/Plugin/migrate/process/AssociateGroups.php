<?php

namespace Drupal\atdove_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\Core\Locale\CountryManager;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;

/**
* Provides a AssociateGroups migrate process plugin.
*
* @MigrateProcessPlugin(
*  id = "associategroups"
* )
*/
class AssociateGroups extends ProcessPluginBase {

 /**
  * {@inheritdoc}
  */
	public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
   
		$related_groups = $row->getSourceProperty('og_org_ref');
		
		return $related_groups;

	}
}