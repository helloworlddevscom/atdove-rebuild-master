<?php

namespace Drupal\atdove_emails;

use Drupal\node\Entity\Node;
use Drupal\group\Entity\Group;
use Drupal\atdove_utilities\ValueFetcher;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\opigno_module\Entity\OpignoActivity;

/**
 * Class for creating emails based on entity events.
 */
class atDoveEmailFactory{

  /**
   * @var \Drupal\Core\Mail\MailManager
   */
  private $mailManager;

  /**
   * @var \Drupal\Core\Language\Language
   */
  private $language;

  public function __construct() {
    $this->mailManager = \Drupal::service('plugin.manager.mail');
    $this->language = \Drupal::service('language.default')->get();
  }

  /**
   * Send a user an email about a new assignment.
   *
   * @param \Drupal\node\Entity\Node $assignment
   *  An "Assignment" node type.
   */
  public function emailUserAboutNewAssignment(Node $assignment) {
    $loadError = FALSE;
    $creator = User::load($assignment->getOwnerId());
    $assignee = User::load(ValueFetcher::getFirstValue($assignment, 'field_assignee'));
    $assignedContentId = ValueFetcher::getFirstValue($assignment, 'field_assigned_content');
    $assignedContent = OpignoActivity::load($assignedContentId);

    if (is_null($assignedContent)) {
      $loadError = TRUE;
      \Drupal::logger('atdove_emails')->error('atDoveEmailFactory::emailUserAboutNewAssignment failed to load assigned content: ' . ValueFetcher::getFirstValue($assignment, 'field_assigned_content'));
    }

    if (is_null($creator)) {
      $loadError = TRUE;
      \Drupal::logger('atdove_emails')->error('atDoveEmailFactory::emailUserAboutNewAssignment failed to creator with UID: ' . $assignment->getOwnerId());
    }

    // If the assignee fails to load, throw error.
    if (is_null($assignee)) {
      $loadError = TRUE;
      \Drupal::logger('atdove_emails')->error('atDoveEmailFactory::emailUserAboutNewAssignment failed to load assignee with UID: ' . ValueFetcher::getFirstValue($assignment, 'field_assignee'));
    }

    // Exit if any expected values fail to load.
    if ($loadError == TRUE) {
      return FALSE;
    }

    $url = Url::fromRoute('entity.opigno_activity.canonical', ['absolute' => TRUE, 'opigno_activity' => $assignedContentId]);
    $assignment_link = Link::fromTextAndUrl($assignedContent->getName(), $url);

    $params['subject'] = t('New assignment on atDove');
    $params['body'] = t('Hi @firstName,<br/><br/>
        Ready to sharpen your skills?<br/><br/>
        Scrub in atDove to see what @creator has assigned for you to complete.
        In order to complete your assignment, you must complete the associated
        quiz with a passing score.<br/><br/>
        Assignment: @assignment<br/><br/>
        Best,<br/>
        The atDove Team
    ', [
      '@firstName' => ValueFetcher::getFirstValue($assignee, 'field_first_name'),
      '@creator' => ValueFetcher::getFirstValue($creator, 'field_first_name') . ' ' . ValueFetcher::getFirstValue($creator, 'field_last_name'),
      '@assignment' => $assignment_link->toString(),
    ])->render();

    $this->mailManager->mail('atdove_emails', NULL, $assignee->getEmail(), $this->language, $params, NULL, TRUE);
  }

  /**
   * Send an email informing a user that the org admin assignments are complete.
   *
   * @param \Drupal\node\Entity\Node $assignment
   *   An "Assignment" node type.
   */
  public function emailOrgAdminAssignentComplete(Node $assignment) {
    $assigner = $assignment->getOwner();
    $assignee = User::load(ValueFetcher::getFirstValue($assignment, 'field_assignee'));
    $assignedContentId = ValueFetcher::getFirstValue($assignment, 'field_assigned_content');
    $assignedContent = OpignoActivity::load($assignedContentId)::load($assignedContentId);
    $gid = ValueFetcher::getFirstValue($assignment, 'field_organization');
    $staffName = ValueFetcher::getFirstValue($assignee, 'field_first_name') . ' ' . ValueFetcher::getFirstValue($assignee, 'field_last_name');

    $params['subject'] = t('@staffName completed an assignment', ['@staffName' => $staffName]);
    $params['body'] = t('Hello,<br/><br/>
      Let us be the first to say, great job!<br/><br/>
      Someone on your team has successfully completed an assignment!<br/><br/>
      @staffName has completed and passed: @assignmentTitle<br/><br/>
      Please visit the <a href="/group/@gid/assignments">Team Member Assignments Page</a> for additional information.<br/><br/>
      Congrats!<br/><br/>
      The atDove Team
    ', [
      '@staffName' => $staffName,
      '@assignmentTitle' => $assignedContent->getName(),
      '@gid' => $gid
    ])->render();

    $this->mailManager->mail('atdove_emails', NULL, $assigner->getEmail(), $this->language, $params, NULL, TRUE);
  }

  /**
   * Send an email to an org admin about a new trial.
   *
   * @param \Drupal\group\Entity\Group $group
   */
  public function emailOrgAdminAboutNewTrial(Group $group) {
    $creator = $group->getOwner();
    $expirationDate = date('M d, Y', strtotime("+7 day"));

    $params['subject'] = t('Thank you for joining atDove!');
    $params['body'] = t('Hi @creatorName,<br/><br/>
        Thank you so much for starting a free trial. We’re excited for you to have you join atDove!<br/><br/>
        Please note: Your seven-day free trial will automatically renew on @expireDate, at which time your
        credit card will be charged.<br/><br/>
        Make the most out of your free trial by adding team members, so everyone can experience atDove
        and get full access to our procedural videos, RACE-approved CE, and medical articles.<br/>
        If you have any questions, visit our Help Center. If you’d like to discuss your account with an
        atDove representative, you are welcome to schedule a meeting with them.<br/><br/>
        We look forward to helping you and your team in your training and education goals!<br/><br/>
        Best,<br/>
        The atDove Team
    ', [
      '@creatorName' => ValueFetcher::getFirstValue($creator, 'field_first_name') . ' ' . ValueFetcher::getFirstValue($creator, 'field_last_name'),
      '@expireDate' => $expirationDate,
    ])->render();

    $this->mailManager->mail('atdove_emails', NULL, $creator->getEmail(), $this->language, $params, NULL, TRUE);
  }

}
