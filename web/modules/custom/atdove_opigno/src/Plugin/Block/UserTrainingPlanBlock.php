<?php

namespace Drupal\atdove_opigno\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\user\Entity\User;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;

/**
 *
 * @Block(
 *  id = "atdove_opigno_training_plan_block",
 *  admin_label = @Translation("AtDove Opigno User training plan block"),
 *  category = @Translation("Opigno"),
 * )
 *
 */
class UserTrainingPlanBlock extends BlockBase {

	/**
	* {@inheritdoc}
	*/
	public function build() {
		$uid = \Drupal::currentUser()->id();
		$connection = \Drupal::database();
		$query = $connection
		  ->select('opigno_learning_path_achievements', 'a')
		  ->fields('a', ['gid', 'name', 'progress', 'status'])
		  ->condition('a.uid', $uid)
		  ->groupBy('a.gid')
		  ->groupBy('a.name')
		  ->groupBy('a.progress')
		  ->groupBy('a.status')
		  ->orderBy('a.name');

		$rows = $query->execute()->fetchAll();
		$table_rows = [];

		// Build table rows.
		foreach ($rows as $row) {
		  $gid = $row->gid;
		  $status = $row->status ?? 'pending';
		  $progress = $row->progress ?? 0;

		  // Generate the details link.
		  $options = [
		    'attributes' => [
		      'class' => ['btn', 'btn-rounded'],
		      'data-user' => $uid,
		      'data-training' => $gid,
		    ],
		  ];
		  $params = ['user' => $uid, 'group' => $gid];

		  // The opigno link doesn't work here for some reason.
		  //$details = Link::createFromRoute($this->t('Details'), 'opigno_statistics.user.training_details', $params, $options)->toRenderable();

		  $details_link = '<a href="group/' . $gid . '/training-statistic/' . $uid . '" class="btn btn-rounded">Details</a>';
		$details_link = render($details_link);		
		$details_link = Markup::create($details_link);
		  $link_to_tp = '<a href="group/' . $gid . '">' . $row->name . '</a>';

		$link_to_tp = render($link_to_tp);		
		$link_to_tp = Markup::create($link_to_tp);

		  $table_rows[] = [
		    'class' => 'training',
		    'data-training' => $gid,
		    'data-user' => $uid,
		    'data' => [
		      ['data' => $link_to_tp ?? '', 'class' => 'name'],
		      ['data' => $progress . '%', 'class' => 'progress'],
		      ['data' => $this->buildStatus($status), 'class' => 'status'],
		      ['data' => $details_link, 'class' => 'details'],
		    ],
		  ];
		}

		return [
		  '#type' => 'table',
		  '#attributes' => [
		    'class' => ['statistics-table', 'content-box'],
		  ],
		  '#header' => [
		    ['data' => $this->t('Training'), 'class' => 'name'],
		    ['data' => $this->t('Progress'), 'class' => 'progress'],
		    ['data' => $this->t('Passed'), 'class' => 'status'],
		    ['data' => $this->t('Details'), 'class' => 'details'],
		  ],
		  '#rows' => $table_rows,
		];
	}

/**
   * Builds render array for a status value.
   *
   * @param string $value
   *   Status.
   *
   * @return array
   *   Render array.
   */
  protected function buildStatus($value) {
    switch (strtolower($value)) {
      default:
      case 'pending':
        $status_icon = 'icon_state_pending';
        $status_text = Markup::create('<i class="fi fi-rr-menu-dots"></i>' . $this->t('Pending'));
        break;

      case 'expired':
        $status_icon = 'icon_state_expired';
        $status_text = Markup::create('<i class="fi fi-rr-cross-small"></i>' . $this->t('Expired'));
        break;

      case 'failed':
        $status_icon = 'icon_state_failed';
        $status_text = Markup::create('<i class="fi fi-rr-cross-small"></i>' . $this->t('Failed'));
        break;

      case 'completed':
      case 'passed':
        $status_icon = 'icon_state_passed';
        $status_text = Markup::create('<i class="fi fi-rr-check"></i>' . $this->t('Success'));
        break;
    }

    return [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#attributes' => [
        'class' => ['icon_state', $status_icon],
      ],
      '#value' => $status_text,
    ];
  }

}