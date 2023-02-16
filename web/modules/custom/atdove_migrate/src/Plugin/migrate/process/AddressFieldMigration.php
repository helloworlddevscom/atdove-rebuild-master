<?php

namespace Drupal\atdove_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\Core\Locale\CountryManager;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;

/**
* Provides a AddressFieldMigration migrate process plugin.
*
* @MigrateProcessPlugin(
*  id = "addressfieldsmigration"
* )
*/
class AddressFieldMigration extends ProcessPluginBase {

 /**
  * {@inheritdoc}
  */
 public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {


    if ($row->getSourceProperty('field_country')[0]['value'] != '') {
      $country_name = $row->getSourceProperty('field_country')[0]['value']; 
    }
    else {
      $country_name = 'United States';
    }

    $address_line1 = $row->getSourceProperty('field_street_address')[0]['value'];
    $address_line2 = '';
    $organization = '';
    $administrative_area = $row->getSourceProperty('field_state')[0]['value'];
    $locality = $row->getSourceProperty('field_city')[0]['value'];
    $given_name = '';
    $family_name = '';
    $postal_code = $row->getSourceProperty('field_postal_code')[0]['value'];

    // To get the country code.
    $country_list = CountryManager::getStandardList();

    $country_code = array_search($country_name, $country_list);

    if ($country_code == NULL || $country_name === 'US' || empty($country_code)) {
      $country_code = 'US';
    }

    // To get the state value of respective country.
    $subdivision_repository = new SubdivisionRepository();
    $states = $subdivision_repository->getAll([$country_code]);
    foreach ($states as $state) {
      $municipalities = $state->getName();
      if ($administrative_area == $municipalities) {
        $state_code = $state->getCode();
      }
    }
    
    // Return new address values from csv.
    $address_new_values = array(
      "langcode" => 'en',
      "country_code" => $country_code,
      "administrative_area" => $state_code,
      "locality" => $locality,
      "postal_code" => $postal_code,
      "address_line1" => $address_line1,
      "address_line2" => $address_line2,
      "given_name" => $given_name,
      "family_name" => $family_name,
      "organization" => $organization,
      "contact" => $contact,
    );
    return $address_new_values;
     
  }
}
