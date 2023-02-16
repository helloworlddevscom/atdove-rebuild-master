<?php

namespace Drupal\atdove_subscription_migrator\Commands;

use Drupal\group\Entity\Group;
use Drush\Commands\DrushCommands;
use Stripe\Customer;
use Drupal\atdove_billing\PaymentConfig;
use Stripe\Price;
use Stripe\Subscription;
use Carbon\Carbon;

class AtDoveSubscriptionMigratorCommand extends DrushCommands {

  public function __construct()
  {
    PaymentConfig::setApiKey();
    parent::__construct();
  }

  /**
   * Migrates customers
   *
   * @param string $dryrun
   *   Argument provided to the drush command.
   * @command atdove_subscription_migrator:migrateCustomers
   * @usage atdove_subscription_migrator:migrateCustomers false
   */
  public function migrateCustomers($dryrun = 'true') {

    $this->output()->writeln('generating migrated customers => dryrun = ' . $dryrun);

    $debuggingFiles = [
      'failedGroupSaves.json',
      'failedGroupSavesResult.json',
      'foundD7EmailsOne.json',
      'foundD7EmailsTwo.json',
      'nullD7Emails.json',
      'rebuildGroupNotFoundByEmail.json',
      'records.json',
      'stripeEmailNotMatchedInD7.json',
      'foundStripeEmail.json'
    ];

    foreach($debuggingFiles as $file) {
      file_put_contents("/files/private/migration-logs/" . $file, "");
    }

    /** load D7 data */
    $d7data = $fields = []; $i = 0;
    $handle = fopen("/code/web/modules/custom/atdove_subscription_migrator/src/Commands/atdove-licenses.csv", "r");
    if ($handle) {
      while (($row = fgetcsv($handle, 4096)) !== false) {
        if (empty($fields)) {
          $fields = $row;
          continue;
        }
        foreach ($row as $k=>$value) {
          $d7data[$i][$fields[$k]] = $value;
        }
        $i++;
      }
      if (!feof($handle)) {
        $this->output()->writeln("Error: unexpected fgets() fail");
      }
      fclose($handle);
    }

    /** load stripe customers */
    $customerData = $fields = []; $i = 0;
    $handle = fopen("/code/web/modules/custom/atdove_subscription_migrator/src/Commands/stripe-customers.csv", "r");
    if ($handle) {
      while (($row = fgetcsv($handle, 4096)) !== false) {
        if (empty($fields)) {
          $fields = $row;
          continue;
        }
        foreach ($row as $k=>$value) {
          $customerData[$i][$fields[$k]] = $value;
        }
        $i++;
      }
      if (!feof($handle)) {
        $this->output()->writeln("Error: unexpected fgets() fail");
      }
      fclose($handle);
    }

    $recordCount = 0;
    $matchCount = 0;
    $saveFailedCount = 0;
    $d7match = 0;

    // Here we identify and log which D7 emails don't have a match in Stripe
    $d7EmailsCount = 0;
    foreach($d7data as $d7) {
      $d7emails = explode("|", $d7['customer_email']);
      foreach($d7emails as $d7email) {
        $this->output()->writeln(sprintf("D7 total emails count: " . ++$d7EmailsCount));
        $stripeMatch = false;
        foreach($customerData as $record) {
          if(trim($d7email) == trim($record['Email'])) {
            $stripeMatch = true;
            break;
          }
        }
        if($stripeMatch === false) {
          $orphanD7Email[] = [
            'email' => $d7email
          ];
          file_put_contents("/files/private/migration-logs/orphanD7Emails.json", json_encode($orphanD7Email));
        } else {
          $foundD7EmailsOne[] = [
            'email' => $d7email
          ];
          file_put_contents("/files/private/migration-logs/foundD7EmailsOne.json", json_encode($foundD7EmailsOne));
        }
      }
    }

    // Process stripe emails
    foreach($customerData as $record) {
      // Here we loop through the exported D7 license data to determine whether the stripe account email corresponds
      // to a D7 account with an active license.
      $orgAdminEmail = null;
      foreach($d7data as $d7) {
        $d7emails = explode("|", $d7['customer_email']);
        foreach($d7emails as $d7email) {
          if(trim($record['Email']) == trim($d7email)) {
            $d7match++;
            //$this->output()->writeln("D7 email match: " . $d7email . ", Stripe email " . $record['Email'] . " |  D7 match count: " . $d7match);
            $orgAdminEmail = $d7email;
            $foundD7EmailsTwo[] = [
              'email' => $d7email
            ];
            file_put_contents("/files/private/migration-logs/foundD7EmailsTwo.json", json_encode($foundD7EmailsTwo));
            break;
          }
        }
      }

      // Skip stripe account record if there was no D7 license match
      if(is_null($orgAdminEmail)) {
        $this->output()->writeln("d7 email not matched in stripe data: " . $record['Email']);
        $stripeNotFound[] = ['email' => $record['Email']];
        file_put_contents("/files/private/migration-logs/stripeEmailNotMatchedInD7.json", json_encode($stripeNotFound));
        continue;
      } else {
        $foundStripeEmail[] = [
          'email' => $record['Email']
        ];
        file_put_contents("/files/private/migration-logs/foundStripeEmail.json", json_encode($foundStripeEmail));
      }

      // Query the group info that corresponds to the active license account email.
      // TODO: ??can we load the group by the user email via php instead??
      $database = \Drupal::database();
      $sql = <<<QUERY
SELECT field_user_org_group_member_groups_field_data.mail, groups_field_data.label, group__field_stripe_customer_id.field_stripe_customer_id_value as 'stripe_id', `groups`.id, `groups`.type, groups_field_data.id AS id, field_user_org_group_member_groups_field_data.uid AS field_user_org_group_member_groups_field_data_uid
FROM `groups`
INNER JOIN groups_field_data ON groups_field_data.id = `groups`.id
INNER JOIN user__field_user_org_group_member user__field_user_org_group_member  ON groups_field_data.id = user__field_user_org_group_member.field_user_org_group_member_target_id AND user__field_user_org_group_member.deleted = '0'
INNER JOIN users_field_data field_user_org_group_member_groups_field_data ON user__field_user_org_group_member.entity_id = field_user_org_group_member_groups_field_data.uid
LEFT JOIN group__field_stripe_customer_id group__field_stripe_customer_id ON groups_field_data.id = group__field_stripe_customer_id.entity_id AND group__field_stripe_customer_id.deleted = '0'
WHERE ((groups_field_data.status = '1') AND (groups_field_data.type IN ('organization')))
AND field_user_org_group_member_groups_field_data.mail = :email
group by field_user_org_group_member_groups_field_data.mail, groups_field_data.label;
QUERY;

      $query = $database->query($sql, [
        ':email' => $record['Email'],
      ]);
      $result = $query->fetchAssoc();

      // Skip if a group wasn't found
      if(!isset($result['id'])) {
        $this->output()->writeln("group match not found for: " . $record['Email']);
        $rebuildGroupNotFoundByEmail[] = ['email' => $record['Email']];
        file_put_contents("/files/private/migration-logs/rebuildGroupNotFoundByEmail.json", json_encode($rebuildGroupNotFoundByEmail));
        continue;
      }

      $this->output()->writeln("MATCH for: " . $record['Email'] . " => match count: " . ++$matchCount);

      if($dryrun !== 'true') {

        $this->output()->writeln(sprintf("dry run false"));

        // Load the group
        $group = Group::load($result['id']);

        // Skip if we don't have a group
        if(!isset($group->id)) {
          $noGroupIdOnGroup[] = ['email' => $record['Email'], 'error' => 'group not found'];
          file_put_contents("/files/private/migration-logs/noGroupIdOnGroup.json", json_encode($noGroupIdOnGroup));
          continue;
        }

        // Set the stripe id on the group
        $group->set('field_stripe_customer_id', $record['id']);

        try {
          $attemptingGroupSave[] = ['email' => $record['Email']];
          file_put_contents("/files/private/migration-logs/attemptingGroupSave.json", json_encode($attemptingGroupSave));
          $groupSave = $group->save();
        } catch(\Exception $e) {
          $saveFailedCount++;
          $this->output()->writeln("Save errorr: " . $e->getMessage());
          $failedSave[] = ['email' => $record['Email'], 'error' => $e->getMessage()];
          file_put_contents("/files/private/migration-logs/failedGroupSaves.json", json_encode($failedSave));
          continue;
        }

        echo "GROUP SAVE: "; echo $groupSave;
        if($groupSave !== 1 && $groupSave !== 2) {
          $failedGroupSaveResult[] = ['email' => $record['Email']];
          file_put_contents("/files/private/migration-logs/failedGroupSavesResult.json", json_encode($failedGroupSaveResult));
        } else {
          $successGroupSaveResult[] = ['email' => $record['Email']];
          file_put_contents("/files/private/migration-logs/successGroupSavesResult.json", json_encode($successGroupSaveResult));
        }

        $recordJson[] = [
          'count' => $recordCount,
          'record' => $record['id'],
          'group' => $group->id->value,
          'email' => $record['Email'],
          'result' => $groupSave
        ];
        file_put_contents("/files/private/migration-logs/records.json", json_encode($recordJson));
        $this->output()->writeln("Save result: " . $groupSave);
      }
      $recordCount++;
      $this->output()->writeln("Save failed count: " . $saveFailedCount);
      //$this->output()->writeln("Record count: " . $recordCount); echo PHP_EOL . PHP_EOL;
    }

    $this->output()->writeln("Customer migration complete");
    return 0;
  }

  /**
   * Migrates subscriptions
   *
   * @param string $dryrun
   *   Argument provided to the drush command.
   * @param int $limit
   *   Argument provided to the drush command.
   * @command atdove_subscription_migrator:migrateSubscriptions
   * @usage atdove_subscription_migrator:migrateSubscriptions
   */
  public function migrateSubscriptions($dryrun = 'true', $limit = 0)
  {
    $this->output()->writeln('generating migrated subscriptions => dryrun = ' . $dryrun . ' limit = ' . $limit);

    $debuggingFiles = [
      'activeSubscriptions.json',
      'stripePriceNotFound.json',
      'pastResumesAtDate.json',
      'farOutExpiration.json',
      'subscriptionCreationError.json',
      'subscriptionCreated.json',
      'customerNotFound.json',
      'customerFound.json'
    ];

    foreach($debuggingFiles as $file) {
      file_put_contents("/files/private/migration-logs/subscriptions/" . $file, "");
    }

    $data = $fields = []; $i = 0;
    $handle = fopen("/code/web/modules/custom/atdove_subscription_migrator/src/Commands/atdove-licenses.csv", "r");
    if ($handle) {
      while (($row = fgetcsv($handle, 4096)) !== false) {
        if (empty($fields)) {
          $fields = $row;
          continue;
        }
        foreach ($row as $k=>$value) {
          $data[$i][$fields[$k]] = $value;
        }
        $i++;
      }
      if (!feof($handle)) {
        $this->output()->writeln("Error: unexpected fgets() fail");
      }
      fclose($handle);
    }

    $testing = 0;
    foreach($data as $record)
    {
      if(empty($record['customer_email'])) {
        continue;
      }

      if($testing >= (int)$limit) {
        $this->output()->writeln("Record limit " . $limit . " reached.");
        break;
      }
      $testing++;

      // Determine existing migrated customer
      $customerResponse = null;
      foreach(explode("|", $record['customer_email']) as $customerEmail) {
        $email = trim($customerEmail);
        $customerResponse = Customer::search([
          'query' => sprintf('email:\'%s\'', $email)
        ]);

        if(!isset($customerResponse->data[0]['id'])) {
          $this->output()->writeln("Error: migrated customer not found => license: " . $record['license_id']);
          $customerNotFound[] = [
            'license' => $record['license_id']
          ];
          file_put_contents("/files/private/migration-logs/subscriptions/customerNotFound.json", json_encode($customerNotFound));
        } else {
          $this->output()->writeln("Customer found: " . $customerResponse->data[0]['id'] . " => license: " . $record['license_id']);
          $customerFound[] = [
            'license' => $record['license_id']
          ];
          file_put_contents("/files/private/migration-logs/subscriptions/customerFound.json", json_encode($customerFound));
          break;
        }
      }

      if(!isset($customerResponse->data[0]['id'])) {
        continue;
      }

      $customer = $customerResponse->data[0];

      $subscriptions = Subscription::all(['customer' => $customer->id]);
      if(isset($subscriptions['data']) && count($subscriptions['data']) > 0) {
        foreach($subscriptions['data'] as $subscription) {
          if($subscription->status !== 'canceled') {
            $activeSubscriptions[] = [
              'license' => $record['license_id']
            ];
            file_put_contents("/files/private/migration-logs/subscriptions/activeSubscriptions.json", json_encode($activeSubscriptions));
            continue 2;
          }
        }
      }

      // Determine Stripe price
      $prices = Price::all([
        'active' => true,
        'type' => 'recurring'
      ])['data'];

      $stripePrice = null;
      foreach($prices as $price)
      {
        if(!isset($price->nickname) || !isset($price->recurring->interval))
        {
          continue;
        }

        $priceSeats = 0;
        if(stripos($price->nickname, "Annual") !== false) {
          $priceSeats = (int)trim($price->nickname, "Annual ");
        } else {
          $priceSeats = (int)$price->nickname;
        }
        $priceInterval = $price->recurring->interval;
        $recordSeats = (int)trim(str_ireplace(" team members", "", str_ireplace("Up to ", "", $record['max_seats'])));
        echo $priceSeats . " : " . $recordSeats . PHP_EOL;
        $recordInterval = $record['interval'] === "yearly" ? "year" : "month";
        echo $priceInterval . " : " . $recordInterval . PHP_EOL;
        if($recordSeats === $priceSeats && $recordInterval === $priceInterval)
        {
          $stripePrice = $price;
          break;
        }
      }

      if(is_null($stripePrice))
      {
        $this->output()->writeln(sprintf("Error: Stripe price not found for license: %d", $record['license_id']));
        $stripePriceNotFound[] = [
          'license' => $record['license_id']
        ];
        file_put_contents("/files/private/migration-logs/subscriptions/stripePriceNotFound.json", json_encode($stripePriceNotFound));
        continue;
      } else {
        $this->output()->writeln(sprintf("Stripe price found for license: %d", $record['license_id']));
      }

      $priceId = $stripePrice->id;

      $resumesAtDate = new Carbon($record['expires']);

      $now = Carbon::now();
      if($resumesAtDate < $now) {
        $pastResumesAtDate[] = [
          'license' => $record['license_id']
        ];
        file_put_contents("/files/private/migration-logs/subscriptions/pastResumesAtDate.json", json_encode($pastResumesAtDate));
        continue;
      }

      if ($dryrun !== 'true') {
        $this->output()->writeln(sprintf("dry run false"));

        $farOut = false;
        $diff = $resumesAtDate->diff($now)->days;
        if($diff > 730) {
          $farOut = true;
          $this->output()->writeln('EXPIRATION GREATER THAN 2 YEARS!');
          $farOutExpiration[] = [
            'license' => $record['license_id']
          ];
          file_put_contents("/files/private/migration-logs/subscriptions/farOutExpiration.json", json_encode($farOutExpiration));
          $priceId = 'price_1Lm2bTFFCEhosNsMHZuTrN10';
        }

        $subscriptionData = [
          'customer' => $customer['id'],
          'items' => [
            ['price' => $priceId],
          ],
          'metadata' => ['license_tier' => $stripePrice['nickname']]
        ];

        if(!$farOut) {
          $subscriptionData['trial_end'] = $resumesAtDate->timestamp;
        }

        if (isset($customer->default_source) && !empty($customer->default_source)) {
          $subscriptionData['default_source'] = $customer->default_source;
        }

        try {
          Subscription::create($subscriptionData);
        } catch(\Exception $e) {
          $this->output()->writeln(sprintf("ERROR CREATING SUBSCRIPTION: " . $e->getMessage()));
          $subscriptionCreationError[] = [
            'license' => $record['license_id']
          ];
          file_put_contents("/files/private/migration-logs/subscriptions/subscriptionCreationError.json", json_encode($subscriptionCreationError));
          continue;
        }

        $this->output()->writeln(sprintf("Subscription created for license: %d", $record['license_id']));
        $subscriptionCreated[] = [
          'license' => $record['license_id']
        ];
        file_put_contents("/files/private/migration-logs/subscriptions/subscriptionCreated.json", json_encode($subscriptionCreated));
      }

      $this->output()->writeln("incrementor: " . $testing);
    }

    $this->output()->writeln('generating migrated subscriptions complete.');
    return 0;
  }

}
