<?php

namespace Drupal\atdove_opigno\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\opigno_messaging\Form\OpignoPrivateMessageThreadForm;
use Drupal\opigno_messaging\Services\OpignoMessageThread;
use Drupal\private_message\Entity\PrivateMessageThreadInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\atdove_opigno\Form\AtDoveAddtoTrainingForm;
use Drupal\atdove_opigno\Form\AtDoveAssignTrainingForm;
use Drupal\atdove_opigno\Form\AtDoveAssignToPersonForm;
use Drupal\atdove_opigno\Form\AtDoveSubmitQuizForm;
use Drupal\opigno_module\Entity\OpignoActivity;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\Entity\GroupContent;

/**
 * The AtDove Opigno Add to training Plan controller.
 *
 * @package Drupal\atdove_opigno\Controller
 */
class AtDoveAddToTrainingController extends ControllerBase {

  /**
   * The DB connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The private messages storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|null
   */
  protected $messageStorage = NULL;

  /**
   * Opigno private messaging manager service.
   *
   * @var \Drupal\opigno_messaging\Services\OpignoMessageThread
   */
  protected $messageService;

  /**
   * OpignoMessageThreadController constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The DB connection service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder service.
   * @param \Drupal\opigno_messaging\Services\OpignoMessageThread $pm_service
   *   The private messages manager service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   */
  public function __construct(
    Connection $database,
    FormBuilderInterface $form_builder,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFormBuilderInterface $entity_form_builder,
    OpignoMessageThread $pm_service,
    AccountInterface $account
  ) {
    $this->database = $database;
    $this->formBuilder = $form_builder;
    $this->entityFormBuilder = $entity_form_builder;
    $this->messageService = $pm_service;
    $this->currentUser = $account;

    try {
      $this->messageStorage = $entity_type_manager->getStorage('private_message');
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
      $container->get('database'),
      $container->get('form_builder'),
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('opigno_messaging.manager'),
      $container->get('current_user')
    );
  }

  /**
   * Prepare the AJAX response to display the thread add/edit form.
   *
   * @param int $tid
   *   The thread ID to get the form for. If 0 is given, creation form will be
   *   rendered.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response object.
   *
   * @throws \Drupal\Core\Form\EnforcedResponseException
   * @throws \Drupal\Core\Form\FormAjaxException
   */
  private function prepareTrainingFormResponse(int $id = 0): AjaxResponse {
    $response = new AjaxResponse();
    $form_state = new FormState();
    $form_state->addBuildInfo('args', [$id]);
    $form = $this->formBuilder->buildForm(AtDoveAddtoTrainingForm::class, $form_state);

    $build = [
      '#theme' => 'opigno_messaging_modal',
      '#title' => 'Add Activity to Training Plan',
      '#body' => $form,
    ];

    $response->addCommand(new RemoveCommand('.modal-ajax'));
    $response->addCommand(new AppendCommand('body', $build));
    $response->addCommand(new InvokeCommand('.modal-ajax', 'modal', ['show']));

    return $response;
  }

  /**
   * Get the private messages thread create form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response object.
   *
   * @throws \Drupal\Core\Form\EnforcedResponseException
   * @throws \Drupal\Core\Form\FormAjaxException
   */
  public function addToTrainingForm(OpignoActivity $opigno_activity): AjaxResponse {
    $id = (int) $opigno_activity->id();
    return $this->prepareTrainingFormResponse($id);
  }

  /**
   * Prepare the AJAX response to display the thread add/edit form.
   *
   * @param int $tid
   *   The thread ID to get the form for. If 0 is given, creation form will be
   *   rendered.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response object.
   *
   * @throws \Drupal\Core\Form\EnforcedResponseException
   * @throws \Drupal\Core\Form\FormAjaxException
   */
  private function prepareAssignTrainingFormResponse(int $id = 0): AjaxResponse {
    $response = new AjaxResponse();
    $form_state = new FormState();
    $form_state->addBuildInfo('args', [$id]);
    $form = $this->formBuilder->buildForm(AtDoveAssignTrainingForm::class, $form_state);

    $build = [
      '#theme' => 'opigno_messaging_modal',
      '#title' => 'Assign Training Plan',
      '#body' => $form,
    ];

    $response->addCommand(new RemoveCommand('.modal-ajax'));
    $response->addCommand(new AppendCommand('body', $build));
    $response->addCommand(new InvokeCommand('.modal-ajax', 'modal', ['show']));

    return $response;
  }

  /**
   * Get the private messages thread create form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response object.
   *
   * @throws \Drupal\Core\Form\EnforcedResponseException
   * @throws \Drupal\Core\Form\FormAjaxException
   */
  public function assignTrainingForm(Group $group): AjaxResponse {
    $id = (int) $group->id();
    return $this->prepareAssignTrainingFormResponse($id);
  }

  /**
   * Prepare the AJAX response to display the thread add/edit form.
   *
   * @param int $tid
   *   The thread ID to get the form for. If 0 is given, creation form will be
   *   rendered.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response object.
   *
   * @throws \Drupal\Core\Form\EnforcedResponseException
   * @throws \Drupal\Core\Form\FormAjaxException
   */
  private function preparePersonFormResponse(int $id = 0): AjaxResponse {
    $response = new AjaxResponse();
    $form_state = new FormState();
    $form_state->addBuildInfo('args', [$id]);
    $form = $this->formBuilder->buildForm(AtDoveAssignToPersonForm::class, $form_state);

    $build = [
      '#theme' => 'opigno_messaging_modal',
      '#title' => 'Assign Activity to Person',
      '#body' => $form,
    ];

    $response->addCommand(new RemoveCommand('.modal-ajax'));
    $response->addCommand(new AppendCommand('body', $build));
    $response->addCommand(new InvokeCommand('.modal-ajax', 'modal', ['show']));

    return $response;
  }

  /**
   * Get the private messages thread create form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response object.
   *
   * @throws \Drupal\Core\Form\EnforcedResponseException
   * @throws \Drupal\Core\Form\FormAjaxException
   */
  public function assignToPersonForm(OpignoActivity $opigno_activity): AjaxResponse {
    $id = (int) $opigno_activity->id();
    return $this->preparePersonFormResponse($id);
  }

  /**
   * Prepare the AJAX response to display the thread add/edit form.
   *
   * @param int $tid
   *   The thread ID to get the form for. If 0 is given, creation form will be
   *   rendered.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response object.
   *
   * @throws \Drupal\Core\Form\EnforcedResponseException
   * @throws \Drupal\Core\Form\FormAjaxException
   */
  private function prepareQuizFormResponse(int $id = 0): AjaxResponse {
    $response = new AjaxResponse();
    $form_state = new FormState();
    $form_state->addBuildInfo('args', [$id]);
    $form = $this->formBuilder->buildForm(AtDoveSubmitQuizForm::class, $form_state);

    $build = [
      '#theme' => 'opigno_messaging_modal',
      '#title' => 'Submit Assigned Quiz',
      '#body' => $form,
    ];

    $response->addCommand(new RemoveCommand('.modal-ajax'));
    $response->addCommand(new AppendCommand('body', $build));
    $response->addCommand(new InvokeCommand('.modal-ajax', 'modal', ['show']));

    return $response;
  }

  /**
   * Get the private messages thread create form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response object.
   *
   * @throws \Drupal\Core\Form\EnforcedResponseException
   * @throws \Drupal\Core\Form\FormAjaxException
   */
  public function submitQuizForm(OpignoActivity $opigno_activity): AjaxResponse {
    $id = (int) $opigno_activity->id();
    return $this->prepareQuizFormResponse($id);
  }


  // /**
  //  * Get the private messages thread edit form.
  //  *
  //  * @param \Drupal\private_message\Entity\PrivateMessageThreadInterface $private_message_thread
  //  *   The thread to get the form for.
  //  *
  //  * @return \Drupal\Core\Ajax\AjaxResponse
  //  *   The AJAX response object.
  //  *
  //  * @throws \Drupal\Core\Form\EnforcedResponseException
  //  * @throws \Drupal\Core\Form\FormAjaxException
  //  */
  // public function getEditThreadForm(PrivateMessageThreadInterface $private_message_thread): AjaxResponse {
  //   $tid = (int) $private_message_thread->id();

  //   return $this->prepareFormResponse($tid);
  // }

  // *
  //  * Get the delete thread confirmation form.
  //  *
  //  * @param \Drupal\private_message\Entity\PrivateMessageThreadInterface $private_message_thread
  //  *   The thread to be deleted.
  //  *
  //  * @return \Drupal\Core\Ajax\AjaxResponse
  //  *   The AJAX response object.
   
  // public function getDeleteThreadForm(PrivateMessageThreadInterface $private_message_thread): AjaxResponse {
  //   $response = new AjaxResponse();
  //   $build = [
  //     '#theme' => 'opigno_confirmation_popup',
  //     '#body' => $this->entityFormBuilder->getForm($private_message_thread, 'delete'),
  //   ];

  //   $response->addCommand(new RemoveCommand('.modal-ajax'));
  //   $response->addCommand(new AppendCommand('body', $build));
  //   $response->addCommand(new InvokeCommand('.modal-ajax', 'modal', ['show']));

  //   return $response;
  // }

  /**
   * Close the modal.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response object.
   */
  public function closeModal(): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new InvokeCommand('.modal', 'modal', ['hide']));

    return $response;
  }

  // /**
  //  * Create the new thread with the given user and redirect to its page.
  //  *
  //  * @param int $uid
  //  *   The user ID to create message thread with.
  //  *
  //  * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
  //  *   The redirect response.
  //  */
  // public function redirectToNewThread(int $uid): ?RedirectResponse {
  //   $members = [(int) $this->currentUser->id(), $uid];
  //   $thread = $this->messageService->getThreadForMembers($members);
  //   if (!$thread instanceof PrivateMessageThreadInterface) {
  //     return NULL;
  //   }

  //   // Prepare the url to the created thread.
  //   $url = Url::fromRoute(
  //     'entity.private_message_thread.canonical',
  //     ['private_message_thread' => $thread->id()]
  //   )->toString();

  //   return new RedirectResponse($url);
  // }

}
