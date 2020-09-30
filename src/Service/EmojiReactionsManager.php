<?php

namespace Drupal\emoji_reactions\Service;

use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\emoji_reactions\Access\CsrfTokenGenerator;

/**
 * Class EmojiReactionsManager.
 */
class EmojiReactionsManager {

  const REACTION_TYPE_ADD = 0;
  const REACTION_TYPE_REMOVE = 1;

  /**
   * Current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\emoji_reactions\Access\CsrfTokenGenerator
   */
  protected $csrfTokenGenerator;

  /**
   * Constructs a new EmojiReactionsManager object.
   */
  public function __construct(AccountProxyInterface $account, EntityTypeManagerInterface $entity_type_manager, CsrfTokenGenerator $csrf_token_generator) {
    $this->account = $account;
    $this->entityTypeManager = $entity_type_manager;
    $this->CsrfTokenGenerator = $csrf_token_generator;
  }

  /**
   * Determine if reaction should by react or remove.
   *
   * @param string $reaction
   *   Reaction type ()
   * @param string $target
   *   Entity Type : bundle.
   * @param string|int $id
   *   Entity Id.
   *
   * @return string
   *   Returns the action type for the given reaction.
   */
  public function getActionType($reaction, $target, $id) {

    return self::REACTION_TYPE_ADD;
  }

  /**
   * Get emoji reactions links by entity.
   */
  public function getLinksByEntity(EntityBase $entity) {

    $target = $entity->getEntityTypeId() . ':' . $entity->bundle();
    $emoji_reactions = $this->getLinks($target, $entity->id());

    return $emoji_reactions;
  }

  /**
   * Get link to like/unlike or count info.
   *
   * @param string $target
   *   Target entity:bundle.
   * @param string $id
   *   Target bundle id.
   *
   * @return array
   *   Render or empty array.
   */
  public function getLinks($target, $id) {

    $config = \Drupal::config('emoji_reactions.settings');
    $target_entities = $config->get('target_entities');

    if (empty($target_entities)) {
      return [];
    }
  }

  /**
   * Set emoji reaction session cookie.
   */
  public function setCookie($session_id) {
    setcookie('likeit_session', $session_id, time() + (86400 * 7), '/');

    // Likeit uses Ajax and page isn't reloading.
    // That is why we manually set $_COOKIE
    // because once the cookies have been set,
    // they can be accessed only on the next page load.
    $_COOKIE['likeit_session'] = $session_id;
  }

  /**
   * Get Emoji Reaction session cookie.
   */
  public function getCookie() {
    return $_COOKIE['likeit_session'] ?? FALSE;
  }

  /**
   * Remove previeously setted colkie cookie.
   *
   * @void
   */
  public function removeCookie() {
    setcookie('likeit_session', '', time() - 3600);
  }

  /**
   * Get current user session id.
   */
  public function getUserSessionId() {
    $session_id = uniqid();
    $user = \Drupal::currentUser();
    if ($user->isAnonymous()) {
      $cookie = $this->getCookie();
      if (!empty($cookie)) {
        $session_id = $cookie;
      }
      else {
        $this->setCookie($session_id);
      }
    }

    return $session_id;
  }

  /**
   * Check if there is an existing reaction for entity and user.
   *
   * @return \Drupal\emoji_reactions\Entity\EmojiReactionInterface|bool
   *   Returns the current emoji reaction entity or FALSE if not setted.
   */
  public function check($entity, AccountInterface $account = NULL) {
    if (empty($account)) {
      $account = \Drupal::currentUser();
    }

    if (!$entity) {
      return FALSE;
    }

    $storage = $this->entityTypeManager->getStorage('emoji_reactions');
    $query = $storage->getQuery()
      ->condition('target_entity_type', $entity->getEntityTypeId())
      ->condition('target_entity_id', $entity->id())
      ->condition('user_id', $account->id());

    // Set session id query parameter for anonymous.
    if ($account->isAnonymous()) {
      $cookie = $this->getCookie();
      if (!$cookie) {
        return FALSE;
      }
      $query = $query->condition('session_id', $cookie);
    }

    $likes = $query->execute();

    if (!empty($likes)) {
      return reset($likes);
    }

    return FALSE;
  }

  /**
   * Set reaction.
   */
  public function setReaction(string $reaction_type, EntityInterface $entity, AccountInterface $account = NULL) {
    /* TODO:
     * - Check if a reaction for entity and account already exists.
     * - If reaction exists and reaction is different, set new reaction type.
     * - If no existing reaction, create a new one.
     */

    if (empty($account)) {
      $account = \Drupal::currentUser();
    }

    $existing_reaction = $this->check($entity, $account);

  }

  /**
   * Remove reaction.
   */
  public function removeReactionFromEntity(EntityInterface $entity, AccountInterface $account = NULL) {
    /* TODO:
     * - Check if a reaction for entity and account already exists.
     * - If reaction exists, remove it.
     */
    $storage = $this->entityTypeManager->getStorage('emoji_reactions');
    $type = $entity->getEntityTypeId();
    $likes = $storage->getQuery()
      ->condition('target_entity_type', $type)
      ->condition('user_id', $account->id())
      ->condition('target_entity_id', $id)
      ->execute();

    $entities = $storage->loadMultiple($likes);
    $storage->delete($entities);
  }

  /**
   *
   */
  public function removeAllFromUser(AccountInterface $account) {
    $storage = $this->entityTypeManager->getStorage('emoji_reactions');
    $likes = $storage->getQuery()
      ->condition('user_id', $account->id())
      ->execute();

    if (!empty($likes)) {
      $action = \Drupal::config('emoji_reactions.settings')
        ->get('after_owner_deletion');
      $entities = $storage->loadMultiple($likes);

      // Set owner to anonymous.
      if ($action == 'set_to_anonymous') {
        /** @var \Drupal\emoji_reactions\Entity\EmojiReactionInterface $emoji_reaction */
        foreach ($entities as $emoji_reaction) {
          $emoji_reaction->setOwnerId(0)
            ->save();
        }
      }
      else {
        // Delete Likeit content.
        $storage->delete($entities);
      }
    }
  }

}
