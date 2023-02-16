<?php

namespace Drupal\atdove_opigno\Form;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\opigno_learning_path\LearningPathMembersManager;
use Drupal\opigno_learning_path\Plugin\LearningPathMembers\RecipientsPlugin;
use Drupal\opigno_messaging\Services\OpignoMessageThread;
use Drupal\private_message\Entity\PrivateMessageInterface;
use Drupal\private_message\Entity\PrivateMessageThreadInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\opigno_group_manager\Entity\OpignoGroupManagedContent;
use Drupal\opigno_module\Entity\OpignoActivity;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\Entity\Node;

/**
 * Custom form to submit Quiz attached to Assignment.
 *
 * @package Drupal\atdove_opigno\Form
 */
class AtDoveSubmitQuizForm extends FormBase
{

  /**
   * The current user ID.
   *
   * @var int
   */
  protected $currentUid;

  /**
   * The loaded current user entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected $currentUser = null;

  /**
   * The PM thread entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|null
   */
  protected $threadStorage = null;

  /**
   * The PM entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|null
   */
  protected $messageStorage = null;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * PM thread view builder service.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $threadViewBuilder;

  /**
   * OpignoPrivateMessageThreadForm constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface                 $account
   *   The current user account.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface        $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface          $date_formatter
   *   The date formatter service.
   * @param \Drupal\opigno_messaging\Services\OpignoMessageThread $pm_service
   *   The private messages manager service.
   */
  public function __construct(
    AccountInterface $account,
    EntityTypeManagerInterface $entity_type_manager,
    DateFormatterInterface $date_formatter
  ) {
    $this->currentUid = (int) $account->id();
    $this->dateFormatter = $date_formatter;
    $this->threadViewBuilder = $entity_type_manager->getViewBuilder('private_message_thread');

    try {
      $this->threadStorage = $entity_type_manager->getStorage('private_message_thread');
      $this->messageStorage = $entity_type_manager->getStorage('private_message');
      $this->currentUser = $entity_type_manager->getStorage('user')->load($this->currentUid);
    }
    catch (PluginNotFoundException | InvalidPluginDefinitionException $e) {
      watchdog_exception('opigno_messaging_exception', $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'atdove_submit_quiz_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, int $id = 0)
  {

    $form['atdove_submit_quiz'] = array(
      '#markup' => '<p>Are you sure you are ready to submit your quiz?</p>', 
    );

    // Actions.
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit Quiz',
      '#ajax' => [
        'callback' => '::ajaxSubmit',
      ],
      '#attributes' => [
        'class' => ['use-ajax-submit'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    parent::validateForm($form, $form_state);

  }

  /**
   * The custom form AJAX submit callback.
   *
   * @param array                                $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function ajaxSubmit(array $form, FormStateInterface $form_state): AjaxResponse
  {
    $response = new AjaxResponse();
    // Check if there are any errors and display them.
    $status = $this->messenger()->all()['status'][0];
    $build = $form_state->getBuildInfo();
    $activity_id = $build['args'][0] ?? 0;

    $url = '/my-certificates';

    $build = [
    '#theme' => 'opigno_messaging_modal',
    '#title' => 'Quiz Answers successfully submitted!',
    ];   

    $response->addCommand(new RemoveCommand('.modal-ajax'));
    $response->addCommand(new AppendCommand('body', $build));
    $response->addCommand(new InvokeCommand('.modal-ajax', 'modal', ['show']));
    $response->addCommand(new RedirectCommand($url));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $build = $form_state->getBuildInfo();
    $activity_id = $build['args'][0] ?? 0;
    $uid =$this->currentUid;
    // Look up h5p content id from activity id
    $opigno_activity = OpignoActivity::load($activity_id);
    $h5p_id = $opigno_activity->get('opigno_h5p')->getValue()[0]['h5p_content_id'];

    // Get score from h5p_points table
    $db_connection = \Drupal::service('database');
    $score = 0;

    $score_query = $db_connection->select('h5p_points', 'hpts')
      ->fields('hpts', ['max_points', 'points'])
      ->condition('hpts.content_id', $h5p_id)
      ->condition('hpts.uid', $uid);
    $score_result = $score_query->execute()->fetchAll();

    $user_points = $score_result[0]->points;
    $max_points = $score_result[0]->max_points;

    // Calculate total score
    // Set pass/fail status in assignment
    if (is_null($score_result)) {
      $score = 0;
    } 

    else {
      $percent_score = ($user_points / $max_points);
      $score = round($percent_score*100);
    }
    if ($score >= 70) {
      $status = 'passed';
    }
    else {
      $status = 'failed';
    }

    // If it exists, find current user's assignment that has this quiz in it
    $quiz_query = $db_connection->select('opigno_activity__field_opigno_quiz', 'foq')
      ->fields('foq', ['entity_id'])
      ->condition('foq.field_opigno_quiz_target_id', $activity_id);
    $related_activity = $quiz_query->execute()->fetchField();

    $assignments = \Drupal::entityTypeManager()->getStorage('node')
      ->loadByProperties(['type' => 'assignment', 'status' => 1, 'field_assigned_content' => $related_activity, 'field_assignee' => $uid]);
    $a_node = reset($assignments);

    if ($a_node) {
      $a_node->field_assignment_status->setValue($status);
      $a_node->field_completed->setValue(date('Y-m-d\TH:i:s', time()));
      $a_node->save();
    }

  }

}
