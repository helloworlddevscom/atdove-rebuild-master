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
use Drupal\opigno_module\Controller\OpignoModuleManagerController;
use Drupal\node\Entity\Node;
use Drupal\group\Entity\Group;
use Drupal\user\Entity\User;

/**
 * Custom form to assign training plan to members or an entire group.
 *
 * @package Drupal\atdove_opigno\Form
 */
class AtDoveAssignTrainingForm extends FormBase {

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
    return 'atdove_opigno_assign_training_plan';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, int $id = 0) {

    //Determine which groups the user can select from.
    $user = \Drupal::currentUser();
    $groups = [];
    $grp_membership_service = \Drupal::service('group.membership_loader');
    $grps = $grp_membership_service->loadByUser($user);

    $options[0] = 'Select a group';
    foreach ($grps as $grp) {
      $groups[] = $grp->getGroup();
    }
    foreach ($groups as $group) {
     $options[$group->id()] = $group->label();
    }

    if (empty($options)) {
      $options[0] = "You do not belong to any groups.";
    }
    $default_group = reset($options);


    $form['add_users'] = array(
      '#type' => 'entity_autocomplete',
      '#title' => t('Assign to Users'),
      '#target_type' => 'user',
      '#default_value' => '',
      '#states' => [
        //show this textfield only if the radio 'other' is selected above
        'enabled' => [
          // Don't mistake :input for the type of field -- it's just a css selector. 
          // You can always use :input or any other css selector here, no matter 
          // whether your source is a select, radio or checkbox element.
          ':input[name="add_group"]' => ['value' => 0],
        ],
      ],

    );
    $form['or_markup'] = array(
      '#markup' => '<p class="add-groups-or-users">OR</p>',
    );
    $form['add_group'] = array(
      '#type' => 'select',
      '#title' => t('Assign to a Group'),
      '#multiple' => FALSE,
      '#default_value' => $default_group,
      '#options' => $options,
      '#states' => [
        //show this textfield only if the radio 'other' is selected above
        'enabled' => [
          // Don't mistake :input for the type of field -- it's just a css selector. 
          // You can always use :input or any other css selector here, no matter
          // whether your source is a select, radio or checkbox element.
          ':input[name="add_users"]' => ['value' => ''],
        ],
      ],
    );
    // $form['instructions'] = array(
    //   '#type' => 'textfield',
    //   '#title' => t('Instructions'),
    //   '#default_value' => 'optional',
    // );
    $form['submit_markup'] = array(
      '#markup' => '<p class="sumbit-line"></p>',
    );
    $form['due_date'] = array(
      '#type' => 'date',
      '#title' => t('Due Date'),
    );

    // Actions.
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Assign Activity',
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
       
    $status = $this->messenger()->all()['status'][0];
   
    // Check if there are any errors and display them.
    if ($form_state->getErrors()) {
      $status_messages = [
        '#type' => 'status_messages',
        '#weight' => -50,
      ];
      $response->addCommand(new HtmlCommand('.modal-ajax .modal-body .opigno-status-messages-container', $status_messages));
      $response->setStatusCode(400);

      return $response;
    }

    $build = $form_state->getBuildInfo();
    $activity_id = $build['args'][0] ?? 0;

    $url = '/opigno-activity-search';

    if ($status == 'Nothing selected') {
      $build = [
        '#theme' => 'opigno_messaging_modal',
        '#title' => 'You must select either a user or a group.',
      ];
    }
    if ($status == 'Activity already assigned') {
      $build = [
        '#theme' => 'opigno_messaging_modal',
        '#title' => 'Activity already assigned to User!',
      ];
    }
    if ($status == NULL) {
      $build = [
        '#theme' => 'opigno_messaging_modal',
        '#title' => 'Activity Assigned to user.',
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
    $training_plan_id = $build['args'][0] ?? 0;
    $user_to_add = $form_state->getValue('add_users');
    $due_date = $form_state->getValue('due_date');
    $group_id = $form_state->getValue('add_group');

    // If nothing was selected
    if (($group_id == NULL || $group_id == 0) && $user_to_add == NULL) {
      $this->messenger()->addStatus("Nothing selected");
    }

    if ($training_plan_id !== NULL) {
      $tp_group = Group::load($training_plan_id);

      if ($user_to_add != NULL) {
        $user = User::load($user_to_add);
        if ($tp_group->getMember($user) === FALSE) {
          $tp_group->addMember($user);
          $tp_group->save();
        }
      }

      // If assigning to a group, look up all users in that group and add to tp_group. 
      if ($group_id != NULL && $group_id != 0) {
          // Load group
          $group = Group::load($group_id);

          // Load all users in group
          $members = $group->getMembers();
          foreach ($members as $member) {
            $user = $member->getUser();
            $uids[] = $user->id();
          }

        foreach ($uids as $user_to_add) {
          $user = User::load($user_to_add);
          if ($tp_group->getMember($user) === FALSE) {

            $tp_group->addMember($user);
            $tp_group->save();
          }
        }
      }
    }
  }
}
