<?php

namespace Drupal\atdove_contributor_bio\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Render\Renderer;
use Symfony\Component\DependencyInjection\ContainerInterface;


class ContributorBio extends ControllerBase
{
  protected $renderer;

  public function __construct(Renderer $renderer) {
    $this->renderer = $renderer;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  public function index(AccountInterface $user, Request $request) {

    $bioView = \Drupal\views\Views::getView('contributor_bio');
    $bioView->setArguments([$user->id()]);
    $bioView->setDisplay('default');
    $bioView->execute();

    $contentView = views_embed_view('contributor_content', 'default', $user->id());
    $contentRender = $this->renderer->render($contentView);

    $contributorData = null;
    foreach ($bioView->result as $id => $result) {
      $node = $result->_entity;

      $contributorData = [
        'title' => $node->get('field_user_title')->value,
        'name' => $node->get('field_first_name')->value . " " . $node->get('field_last_name')->value,
        'school' => $node->get('field_user_school')->value,
        'current_school' => $node->get('field_user_current_school')->value,
        'memberships' => $node->get('field_user_prof_memberships')->value,
        'interests' => $node->get('field_user_prof_interests')->value,
        'bio' => $node->get('field_user_bio')->value,
        'picture' => sprintf(
          "/sites/default/files/%s",
          str_replace('public://', '', $node->user_picture->entity->getFileUri())
        ),
        'content' => $contentRender
      ];
    }

    return [
      // Your theme hook name.
      '#theme' => 'atdove_contributor_bio__bio',
      // Your variables.
      '#contributorData' => $contributorData
    ];
  }
}
