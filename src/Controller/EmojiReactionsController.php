<?php

namespace Drupal\emoji_reactions\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
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
   *
   */
  public function react($reaction, $target, $id, $html_id, $token) {

  }

  /**
   *
   */
  public function remove($reaction, $target, $id, $html_id, $token) {
    $session_id = $this->emojiReactionsManager->getUserSessionId();
    return $this->response($target, $id, $session_id, $html_id);
  }

  /**
   *
   */
  public function response($target, $id, $session_id, $html_id) {
    $response = new AjaxResponse();
    $account = $this->currentUser();
    if ($account->isAnonymous() && !likeit_get_cookie()) {
      likeit_set_cookie($session_id);
    }
  }

}
