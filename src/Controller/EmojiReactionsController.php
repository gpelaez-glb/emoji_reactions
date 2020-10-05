<?php

namespace Drupal\emoji_reactions\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\emoji_reactions\Event\EmojiReactionEvent;
use Drupal\emoji_reactions\Event\EmojiReactionEvents;
use Drupal\emoji_reactions\Service\EmojiReactionsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Undocumented class.
 */
class EmojiReactionsController extends ControllerBase {

  /**
   * Custom token generator service.
   *
   * @var \Drupal\emoji_reactions\Service\EmojiReactionsManager
   */
  protected $emojiReactionsManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * EmojiReactionsController constructor.
   *
   * @param \Drupal\emoji_reactions\Service\EmojiReactionsManager $emojiReactionsManager
   *   The the custom token generator.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(EmojiReactionsManager $er_manager, RequestStack $request_stack, EventDispatcherInterface $event_dispatcher) {
    $this->emojiReactionsManager = $er_manager;
    $this->requestStack = $request_stack;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('emoji_reactions.manager'),
      $container->get('request_stack'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Checks if the token is valid.
   */
  private function validateToken(string $token, string $html_id) {
    return $this->emojiReactionsManager->validateToken($token, $html_id);
  }

  /**
   *
   */
  public function react($reaction_name, $target, $id, $html_id, $token) {
    
    // Validate provided token.
    if ($this->validateToken($token, $html_id)) {

      // Load related entity.
      $entity_arr = explode(':', $target);
      $entity = $this->entityTypeManager()->getStorage($entity_arr[0])->load($id);

      // Perform reaction.
      $reaction_entity = $this->emojiReactionsManager->setReaction($reaction_name, $entity);

      // Create a react/remove event.
      $event = new EmojiReactionEvent($entity, $reaction_entity->getType(), $reaction_entity->getOwner());

      // Use the event dispatcher service to notify any event subscribers.
      $this->eventDispatcher->dispatch(EmojiReactionEvents::REACT, $event);
    }

    $session_id = $this->emojiReactionsManager->getUserSessionId();
    return $this->response($target, $id, $html_id);
  }

  /**
   *
   */
  public function remove($reaction_name, $target, $id, $html_id, $token) {

     // Validate provided token.
     if ($this->validateToken($token, $html_id)) {

      // Load related entity.
      $entity_arr = explode(':', $target);
      $entity = $this->entityTypeManager()->getStorage($entity_arr[0])->load($id);

      // Perform reaction.
      $this->emojiReactionsManager->removeReaction($reaction_name, $entity);

      // Create a react/remove event.
      $type_id = $this->emojiReactionsManager->getReactionTypeIdFromName($reaction_name);
      $type = $this->entityTypeManager()->getStorage('emoji_reaction_type')->load($type_id);
      $event = new EmojiReactionEvent($entity, $type, $this->currentUser());

      // Use the event dispatcher service to notify any event subscribers.
      $this->eventDispatcher->dispatch(EmojiReactionEvents::REACT, $event);
    }
    
    return $this->response($target, $id, $html_id);
  }

  /**
   *
   */
  public function response($target, $id, $html_id) {
    $account = $this->currentUser();
    $session_id = $this->emojiReactionsManager->getUserSessionId();
    if ($account->isAnonymous() && !$this->emojiReactionsManager->getCookie()) {
      $this->emojiReactionsManager->setCookie($session_id);
    }

    $response = new AjaxResponse();
    // TODO: Ajax response to update reactions count.


    // TODO: Ajax response for a toast message.
    
    return $response;
  }

  /**
   * Check user permissions to react/remove/view.
   *
   * @param string $action
   *   Action name.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   User account or null.
   *
   * @return bool
   *   Access grant status.
   */
  public static function checkAccess($action = 'react', AccountInterface $account = NULL) {
    if (!$account) {
      $account = \Drupal::currentUser();
    }

    switch ($action) {
      case 'react':
        return $account->hasPermission('emoji_reactions_react');

      case 'remove':
        return $account->hasPermission('emoji_reactions_remove');

      default:
        return $account->hasPermission('emoji_reactions_view');
    }
  }

}
