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
use Dompdf\Dompdf;
use Drupal\user\Entity\User;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * The AtDove Opigno Certificate controller.
 *
 * @package Drupal\atdove_opigno\Controller
 */
class AtDoveCertificateController extends ControllerBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * Callback to view the opigno_certificate entity attached to any entity.
   */
  public function viewPdf($h5p_id, $user_id = NULL) {

    if (!$user_id) {
      $user_id = $this->currentUser->id();
    }
    
    $current_user = User::load($user_id);
    $user_full_name = $current_user->field_first_name->value . ' ' . $current_user->field_last_name->value;
    $connection = \Drupal::database();

    $completed_date = $connection->select('h5p_points', 'hp')
      ->fields('hp', ['finished'])
      ->condition('uid', $user_id)
      ->condition('content_id', $h5p_id)
      ->execute()
      ->fetchField();

    $completed_date = date('F j, Y', $completed_date);

    //Look up related content to the quiz

    $related_activity_id = $connection->select('opigno_activity__opigno_h5p', 'ophp')
      ->fields('ophp', ['entity_id'])
      ->condition('opigno_h5p_h5p_content_id', $h5p_id)
      ->execute()
      ->fetchField();

    $related_activity = $connection->select('opigno_activity__field_opigno_quiz', 'opq')
      ->fields('opq', ['entity_id'])
      ->condition('field_opigno_quiz_target_id', $related_activity_id)
      ->execute()
      ->fetchField();

    $opigno_activity = OpignoActivity::load($related_activity);
    if ($opigno_activity) {
      $act_name = $opigno_activity->name->getValue()[0]['value'];
      $credit_hours = $opigno_activity->field_credit_hours->getValue();
      $accreditation_info = $opigno_activity->field_accreditation_info->getValue();
      $ce_matter_category = $opigno_activity->field_ce_matter_category->getValue();
      $content_category = $opigno_activity->field_content_category->getValue();
      // Assume it is not accredited first.
      $is_acc = FALSE;

      if ($credit_hours) {
        $credit_hours = $opigno_activity->field_credit_hours->getValue()[0]['value'];
      }
      // If it's a video and has an accredited for value
      if ($opigno_activity->field_accredited_for && $opigno_activity->field_accredited_for->getValue()) {
        if ($opigno_activity->field_accredited_for->getValue()[0]['value']) {
          $is_acc = TRUE;
        }
      }
      $vhma_program_no = NULL;
      $race_program_no = NULL;

      // For accreditations, if the taxonomy term #1 or #2 is listed, it is a RACE/VHMA content
      if (isset($accreditation_info[0])) {
        $acc_p = Paragraph::load($accreditation_info[0]['target_id']);
        if (isset($acc_p->field_p_accreditations->getValue()[0]) && $acc_p->field_p_accreditations->getValue()[0]['target_id'] != 0) {
          $is_acc = TRUE;
          // If RACE
          if ($acc_p->field_p_accreditations->getValue()[0]['target_id'] === "1") {  
            $race_program_no = $acc_p->field_p_accreditation_id->getValue()[0]['value'];
          }
          // If VHMA
          if ($acc_p->field_p_accreditations->getValue()[0]['target_id'] === "2") {
            $vhma_program_no = $acc_p->field_p_accreditation_id->getValue()[0]['value'];
          }
        }
      }
      if (isset($accreditation_info[1])) {
        $acc_p = Paragraph::load($accreditation_info[1]['target_id']);
        if (isset($acc_p->field_p_accreditations->getValue()[0]) && $acc_p->field_p_accreditations->getValue()[0]['target_id'] != 0) {
          $is_acc = TRUE;
          // If RACE
          if ($acc_p->field_p_accreditations->getValue()[0]['target_id'] === "1") {
            $race_program_no = $acc_p->field_p_accreditation_id->getValue()[0]['value'];
          }
          // If VHMA
          if ($acc_p->field_p_accreditations->getValue()[0]['target_id'] === "2") {
            $vhma_program_no = $acc_p->field_p_accreditation_id->getValue()[0]['value'];
          }
        }
      }

      //But also assuming it is also RACE if it contains the content category taxonomy term 'RACE'
      // foreach ($content_category as $key => $value) {
      //   if (in_array($value['target_id'], ['1', '9591'])) {
      //     $is_acc = TRUE;
      //   }
      // }

      if (isset($ce_matter_category[0]['value'])) {
        if ($ce_matter_category[0]['value'] == 'Scientific Program') {
          $ce_matter_category = 'Medical';
        }
        elseif ($ce_matter_category[0]['value'] == 'Non-Scientific Clinical' || $ce_matter_category[0]['value'] == 'Non-Scientific Pract. Mgmt, Prof. Dev.') {
          $ce_matter_category = 'Non-Medical';
        } 
      }
      // Contributors
      if ($opigno_activity->field_contributors != NULL) {
        $contributors = $opigno_activity->field_contributors->getValue();
        foreach ($contributors as $key => $value) {
          $uid = $contributors[$key]['target_id'];
          $user = User::load($uid);
          if ($user != NULL) {
            $contributors[] = $user->field_first_name->value . " "  . $user->field_last_name->value;
          }
        }
      }
    }

    $renderable = [
      '#theme' => 'atdove_certificate',
      '#user_full_name' => $user_full_name,
      '#h5p_id' => $h5p_id,
      '#act_name' => $act_name,
      '#contributors' => $contributors,
      '#completed_date' => $completed_date,
      '#credit_hours' => $credit_hours,
      '#ce_matter_category' => $ce_matter_category,
      '#race_program_no' => $race_program_no,
      '#vhma_program_no' => $vhma_program_no,
      '#is_acc' => $is_acc,
      '#title' => $this->t('Certificate of Completion'),
    ];

    $filename = $user_full_name . '-' . $act_name . '-' . $completed_date . '.pdf';
    $rendered = \Drupal::service('renderer')->renderPlain($renderable);
    $mpdf = new \Mpdf\Mpdf(['tempDir' => 'sites/default/files/tmp']); 
    $mpdf->WriteHTML($rendered);
    $mpdf->Output($filename, 'D');
    Exit;
  }

  /**
   * {@inheritdoc}
   */
  public function view($h5p_id, $user_id = NULL) {

    if (!$user_id) {
      $user_id = $this->currentUser->id();
    }
    $current_user = User::load($user_id);
    $user_full_name = $current_user->field_first_name->value . ' ' . $current_user->field_last_name->value;
    $connection = \Drupal::database();

    $completed_date = $connection->select('h5p_points', 'hp')
      ->fields('hp', ['finished'])
      ->condition('uid', $user_id)
      ->condition('content_id', $h5p_id)
      ->execute()
      ->fetchField();

    $completed_date = date('F j, Y', $completed_date);
    //Look up related content to the quiz

    $related_activity_id = $connection->select('opigno_activity__opigno_h5p', 'ophp')
      ->fields('ophp', ['entity_id'])
      ->condition('opigno_h5p_h5p_content_id', $h5p_id)
      ->execute()
      ->fetchField();

    $related_activity = $connection->select('opigno_activity__field_opigno_quiz', 'opq')
      ->fields('opq', ['entity_id'])
      ->condition('field_opigno_quiz_target_id', $related_activity_id)
      ->execute()
      ->fetchField();

    $opigno_activity = OpignoActivity::load($related_activity);
    if ($opigno_activity) {
      $act_name = $opigno_activity->name->getValue()[0]['value'];
      $credit_hours = $opigno_activity->field_credit_hours->getValue();
      $accreditation_info = $opigno_activity->field_accreditation_info->getValue();
      $ce_matter_category = $opigno_activity->field_ce_matter_category->getValue();
      $content_category = $opigno_activity->field_content_category->getValue();
      // Assume it is not accredited first.
      $is_acc = FALSE;

      if ($credit_hours) {
        $credit_hours = $opigno_activity->field_credit_hours->getValue()[0]['value'];
      }

      $vhma_program_no = NULL;
      $race_program_no = NULL;

      // For accreditations, if the taxonomy term #1 or #2 is listed, it is a RACE/VHMA content
      if (isset($accreditation_info[0])) {
        $acc_p = Paragraph::load($accreditation_info[0]['target_id']);
        if (isset($acc_p->field_p_accreditations->getValue()[0]) && $acc_p->field_p_accreditations->getValue()[0]['target_id'] != 0) {
          $is_acc = TRUE;
          // If RACE, set program number.
          if ($acc_p->field_p_accreditations->getValue()[0]['target_id'] === "1") {  
            $race_program_no = $acc_p->field_p_accreditation_id->getValue()[0]['value'];
          }
          // If VHMA, set program number.
          if ($acc_p->field_p_accreditations->getValue()[0]['target_id'] === "2") {
            $vhma_program_no = $acc_p->field_p_accreditation_id->getValue()[0]['value'];
          }
        }
      }
      if (isset($accreditation_info[1])) {
        $acc_p = Paragraph::load($accreditation_info[1]['target_id']);
        if (isset($acc_p->field_p_accreditations->getValue()[0]) && $acc_p->field_p_accreditations->getValue()[0]['target_id'] != 0) {
          $is_acc = TRUE;
          // If RACE
          if ($acc_p->field_p_accreditations->getValue()[0]['target_id'] === "1") {
            $race_program_no = $acc_p->field_p_accreditation_id->getValue()[0]['value'];
          }
          // If VHMA
          if ($acc_p->field_p_accreditations->getValue()[0]['target_id'] === "2") {
            $vhma_program_no = $acc_p->field_p_accreditation_id->getValue()[0]['value'];
          }
        }
      }

      //But also assuming it is also RACE if it contains the content category taxonomy term 'RACE'
      // foreach ($content_category as $key => $value) {
      //   if (in_array($value['target_id'], ['1', '9591'])) {
      //     $is_acc = TRUE;
      //   }
      // }

      if (isset($ce_matter_category[0]['value'])) {
        if ($ce_matter_category[0]['value'] == 'Scientific Program') {
          $ce_matter_category = 'Medical';
        }
        elseif ($ce_matter_category[0]['value'] == 'Non-Scientific Clinical' || $ce_matter_category[0]['value'] == 'Non-Scientific Pract. Mgmt, Prof. Dev.') {
          $ce_matter_category = 'Non-Medical';
        } 
      }
      // Contributors
      if ($opigno_activity->field_contributors != NULL) {
        $contributors = $opigno_activity->field_contributors->getValue();
        foreach ($contributors as $key => $value) {
          $uid = $contributors[$key]['target_id'];
          $user = User::load($uid);
          if ($user != NULL) {
            $contributors[] = $user->field_first_name->value . " "  . $user->field_last_name->value;
          }
        }
      }
    }

    $renderable = [
      '#theme' => 'atdove_certificate',
      '#user_full_name' => $user_full_name,
      '#h5p_id' => $h5p_id,
      '#act_name' => $act_name,
      '#contributors' => $contributors,
      '#completed_date' => $completed_date,
      '#credit_hours' => $credit_hours,
      '#ce_matter_category' => $ce_matter_category,
      '#race_program_no' => $race_program_no,
      '#vhma_program_no' => $vhma_program_no,
      '#is_acc' => $is_acc,
      '#title' => $this->t('Certificate of Completion'),
    ];

    return $renderable;
  }

  // TODO: ACCESS CONTROL function

}
