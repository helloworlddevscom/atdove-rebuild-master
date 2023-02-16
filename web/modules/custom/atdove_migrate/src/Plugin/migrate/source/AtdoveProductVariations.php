<?php

namespace Drupal\atdove_migrate\Plugin\migrate\source;

use CommerceGuys\Intl\Currency\CurrencyRepository;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;

/**
 * Gets Commerce 1 commerce_product data from database.
 *
 * @MigrateSource(
 *   id = "atdove_product",
 *   source_module = "commerce_product"
 * )
 */
class AtdoveProductVariations extends FieldableEntity {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'product_id' => $this->t('Product variation ID'),
      'sku' => $this->t('SKU'),
      'title' => $this->t('Title'),
      'type' => $this->t('Type'),
      'language' => $this->t('Language'),
      'status' => $this->t('Status'),
      'created' => $this->t('Created'),
      'changed' => $this->t('Changes'),
      'data' => $this->t('Data'),
      'commerce_price' => $this->t('Price with amount, currency_code and fraction_digits'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['product_id']['type'] = 'integer';
    $ids['product_id']['alias'] = 'p';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('commerce_product', 'p')->fields('p');
    if (isset($this->configuration['product_variation_type'])) {
      $query->condition('p.type', $this->configuration['product_variation_type']);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $product_id = $row->getSourceProperty('product_id');
    $revision_id = $row->getSourceProperty('revision_id');
    foreach (array_keys($this->getFields('commerce_product', $row->getSourceProperty('type'))) as $field) {
      $row->setSourceProperty($field, $this->getFieldValues('commerce_product', $field, $product_id, $revision_id));
    }

    // Include the number of currency fraction digits in the price.
    $currencyRepository = new CurrencyRepository();
    $value = $row->getSourceProperty('commerce_price');
    $currency_code = $value[0]['currency_code'];
    $value[0]['fraction_digits'] = $currencyRepository->get($currency_code)->getFractionDigits();
    $row->setSourceProperty('commerce_price', $value);

    //Determine the billing type for this product (Variation)

    // $license_type = 'role';
    $subscription_type ='product_variation';
   // $license_expiration = 0;

    $license_expiration = [];
    $billing_schedule = 'atdove_yearly_billing';

    $row->setSourceProperty('subscription_type', $subscription_type);
    //$row->setSourceProperty('license_type', $license_type);
    $row->setSourceProperty('license_expiration', $license_expiration);
    $row->setSourceProperty('billing_schedule', $billing_schedule);
    return parent::prepareRow($row);
  }

}
