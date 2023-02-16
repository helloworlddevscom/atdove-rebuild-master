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

/**
 * Custom form to create/edit private message thread.
 *
 * @package Drupal\atdove_opigno\Form
 */
class AtDoveAddtoTrainingForm extends FormBase {

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
  protected $currentUser = NULL;

  /**
   * The PM thread entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|null
   */
  protected $threadStorage = NULL;

  /**
   * The PM entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|null
   */
  protected $messageStorage = NULL;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Learning path members manager service.
   *
   * @var \Drupal\opigno_learning_path\LearningPathMembersManager
   */
  protected $lpMembersManager;

  /**
   * Opigno PM manager service.
   *
   * @var \Drupal\opigno_messaging\Services\OpignoMessageThread
   */
  protected $pmService;

  /**
   * PM thread view builder service.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $threadViewBuilder;

  /**
   * OpignoPrivateMessageThreadForm constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\opigno_learning_path\LearningPathMembersManager $lp_members_manager
   *   The LP members manager service.
   * @param \Drupal\opigno_messaging\Services\OpignoMessageThread $pm_service
   *   The private messages manager service.
   */
  public function __construct(
    AccountInterface $account,
    EntityTypeManagerInterface $entity_type_manager,
    DateFormatterInterface $date_formatter,
    LearningPathMembersManager $lp_members_manager,
    OpignoMessageThread $pm_service
  ) {
    $this->currentUid = (int) $account->id();
    $this->dateFormatter = $date_formatter;
    $this->lpMembersManager = $lp_members_manager;
    $this->pmService = $pm_service;
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
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('opigno_learning_path.members.manager'),
      $container->get('opigno_messaging.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'atdove_opigno_add_to_training_plan';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, int $id = 0) {

    $form['training_plans'] = array(
      '#type' => 'entity_autocomplete',
      '#title' => t('Training Plans'),
    //  '#entity_type' => 'group',
      // '#bundles' => array('learning_path'),
      //'#target_type' => '',
      '#target_type' => 'group',
      '#default_value' => '',
        '#selection_settings' => array(
      'target_bundles' => array('learning_path'),
      ),
    );

    $form['add_new_training'] = array(
      '#markup' => '<p>Don\'t see your Training Plan?</p><a href="/group/add/learning_path">Add New Training Plan</a>', 
    );


    // Actions.
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Add Activity',
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
 
  }

  /**
   * Hide/show members selector depending on the checkbox value.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response object.
   */
  public function showMembersAjax(array $form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $command = $form_state->getValue('edit_members') ? 'removeClass' : 'addClass';
    $response->addCommand(new InvokeCommand('#users-to-send', $command, ['hidden']));

    return $response;
  }

  /**
   * The custom form AJAX submit callback.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function ajaxSubmit(array $form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    // Check if there are any errors and display them.
    $status = $this->messenger()->all()['status'][0];
    if ($form_state->getErrors()) {
      $status_messages = [
        '#type' => 'status_messages',
        '#weight' => -50,
      ];
      $response->addCommand(new HtmlCommand('.modal-ajax .modal-body .opigno-status-messages-container', $status_messages));
      $response->setStatusCode(400);

      return $response;
    }

    $training_plan = $form_state->getValue('training_plans');

    $build = $form_state->getBuildInfo();
    $activity_id = $build['args'][0] ?? 0;

    $url = '/opigno-activity-search';
    if ($status == 'Activity already in Training Plan') {
      $build = [
        '#theme' => 'opigno_messaging_modal',
        '#title' => 'Activity already in Training Plan!',
      ];
    }
    else {
      $build = [
        '#theme' => 'opigno_messaging_modal',
        '#title' => 'Activity Added to Training Plan!',
      ];    
    }


    $response->addCommand(new RemoveCommand('.modal-ajax'));
    $response->addCommand(new AppendCommand('body', $build));
    $response->addCommand(new InvokeCommand('.modal-ajax', 'modal', ['show']));
    $response->addCommand(new RedirectCommand($url));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $build = $form_state->getBuildInfo();
    $activity_id = $build['args'][0] ?? 0;

    //Add 
    $training_plan = $form_state->getValue('training_plans');
    $opigno_activity = OpignoActivity::load($activity_id);
    $activities = [
      0 => $opigno_activity,
    ];

    $group = \Drupal\group\Entity\Group::load($training_plan);
    $g_title = $group->label;
    $entity_type_manager = \Drupal::entityTypeManager();

    // Check to see if module exists first. The user ID is used to creat a module in their unique training plan
    //$module = opigno_module::load([First module available in training_plan?]);
    $managed_content = OpignoGroupManagedContent::getFirstStep($group->id());
    $content_type_manager = \Drupal::service('opigno_group_manager.content_types.manager');
     if (!empty($managed_content)) {
      $module_id = $managed_content->getEntityId();
      $type_id = $managed_content->getGroupContentTypeId();
      $type = $content_type_manager->createInstance($type_id);
      /** @var \Drupal\opigno_group_manager\OpignoGroupContent $content */
      $content = $type->getContent($module_id);
      $module = \Drupal::entityTypeManager()->getStorage('opigno_module')->load($module_id);
      $opigno_module_controller = \Drupal::service('opigno_module.opigno_module');
      $add_activities = $opigno_module_controller->activitiesToModule($activities, $module);
      if ($add_activities == FALSE) {
        $this->messenger()->addStatus("Activity already in Training Plan");
      }

    }
    // Create module.
    if (!isset($module)) {

      $module = $entity_type_manager->getStorage('opigno_module')->create([
        'name' => $g_title . ' Module',
      ]);

      $module->save();
      $opigno_module_controller = \Drupal::service('opigno_module.opigno_module');
      $add_activities = $opigno_module_controller->activitiesToModule($activities, $module);
      if ($add_activities == FALSE) {
        $this->messenger()->addStatus("Activity already in Training Plan");
      }
      // Add module as group content to Training Plan
      $group_content = OpignoGroupManagedContent::createWithValues($group->id(),'ContentTypeModule',$module->id(),0,1);
      $group_content->save();
      // // Add module as group content to Training Plan

      $content_types_manager = \Drupal::service('opigno_group_manager.content_types.manager');
      $plugin_definition = $content_types_manager->getDefinition('ContentTypeModule');
      $added_entity = \Drupal::entityTypeManager()
        ->getStorage($plugin_definition['entity_type'])
        ->load($module->id());
      $group->addContent($added_entity, $plugin_definition['group_content_plugin_id']);
    }

    $group->save();

  }

}
