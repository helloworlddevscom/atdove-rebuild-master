<?php
/**
 * @file
 * Contains \Drupal\atdove_migrate\Plugin\migrate\process\EmptyIfUrlAliasExists.
 *
 */

namespace Drupal\atdove_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * This process plugin can be used for path/alias fields as long as the following issues are not resolved. It is a workaround for:
 *
 * https://www.drupal.org/node/2350135#comment-9476629
 * https://drupal.stackexchange.com/questions/238393/migrate-duplicate-entries-in-url-alias-after-update-migrations)
 *
 * @MigrateProcessPlugin(
 *   id = "empty_if_url_alias_exists",
 * )
 */
class EmptyIfUrlAliasExists extends ProcessPluginBase {
    /**
     * {@inheritdoc}
     */
    public function transform(
        $value,
        MigrateExecutableInterface $migrate_executable,
        Row $row,
        $destination_property
    ) {
        // Retrieves a \Drupal\Core\Database\Connection which is a PDO instance
        $db = Database::getConnection();

        $sth = $db->select('url_alias', 'u')
            ->fields('u', ['pid']);
        $and = $sth->andConditionGroup()
            ->condition('u.source', '/node/' . $row->getIdMap()['destid1'])
            ->condition('u.alias', $value);
        $sth->condition($and);
        $data = $sth->execute();

        $results = $data->fetch(\PDO::FETCH_NUM);

        //when no url_alias record found, return the url, so it can be added.
        if ($results === false || count($results) === 0) {
            return $value;
        }
        //when an url_alias record is already present, return null, so the migration does not add it again
        return null;
    }
}