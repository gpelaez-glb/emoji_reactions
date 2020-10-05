<?php

namespace Drupal\emoji_reactions\Service;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\emoji_reactions\Access\CsrfTokenGenerator;
use Drupal\emoji_reactions\Controller\EmojiReactionsController;
use Drupal\emoji_reactions\Entity\EmojiReaction;
use Drupal\emoji_reactions\Entity\EmojiReactionType;
use Drupal\user\UserInterface;

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
    $this->csrfTokenGenerator = $csrf_token_generator;
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
   *
   * @param \Drupal\Core\Entity\EntityBase $entity
   *   The content entity to build reaction links.
   *
   * @return array
   *   Emoji reactions links render array.
   */
  public function getLinksByEntity(EntityBase $entity) {
    $config = \Drupal::config('emoji_reactions.settings');
    $target_entities = $config->get('target_entities');
    $emoji_reactions = [];

    if (!empty($target_entities)) {
      $target = $entity->getEntityTypeId() . ':' . $entity->bundle();
      if (in_array($target, $target_entities)) {
        /** @var \Drupal\emoji_reactions\Entity\EmojiReactionType[] $types */
        $types = $this->entityTypeManager->getStorage('emoji_reaction_type')
          ->loadMultiple();
        foreach ($types as $type) {
          $emoji_reactions[] = $this->buildReactionLink($entity, $type);
        }
      }
    }

    return [
      '#theme' => 'reactions_button',
      '#reactions' =>  $emoji_reactions,
    ];
  }

  /**
   *
   */
  public function getCount(EntityBase $entity, EmojiReactionType $reaction_type = NULL) {
    $storage = \Drupal::entityTypeManager()->getStorage('emoji_reaction');

    $query = $storage->getQuery()
      ->condition('target_entity_id', $entity->id());

    // If reaction type name is given, add reaction to the query.
    if (!empty($reaction_type)) {
      $query->condition('reaction_type_id', $reaction_type->id());
    }

    return $query->count()->execute();

  }

  /**
   *
   */
  public function buildReactionLink(EntityBase $entity, EmojiReactionType $reaction_type) {
    $current_reaction = $this->check($entity, /* NULL, TODO $reaction_type */);
    $action = $current_reaction == FALSE ? 'react' : 'remove';

    $title = $reaction_type->getName();

    $target = $entity->getEntityTypeId() . ':' . $entity->bundle();
    $html_id = uniqid('em-reaction-' . $entity->id());
    $html_id = Html::getId($html_id);

    $url = Url::fromRoute('emoji_reactions.' . $action, [
      'reaction_name' => $reaction_type->getName(),
      'target' => $target,
      'id' => $entity->id(),
      'html_id' => $html_id,
      'token' => $this->csrfTokenGenerator->get($html_id),
    ]);

    $link = [
      '#type' => 'link',
      '#title' => $title,
      '#url' => $url,
    ];
    $link['#attributes']['title'] = $title;
    $link['#attributes']['class'] = ['use-ajax', 'emoji-reaction'];
    $link['#attributes']['id'] = $html_id;

    if ($action == 'remove') {
      $link['#attributes']['class'][] = 'active';
    }

    if (EmojiReactionsController::checkAccess('view', $this->account)) {
      $count = $this->getCount($entity, $reaction_type);

      $link_content = [];
      $link_content[] = $reaction_type->getReactionTypeIcon();
      $link_content[] = [
        '#type' => 'markup',
        '#markup' => '<span class="emoji-reaction--count">' . $count . '</span> ' .
        '<span class="emoji-reaction--title">' . $title . '</span>',
      ];

      $link['#title'] = render($link_content);
    }
    $icon = $reaction_type->getReactionTypeIcon();
    return [
      '#theme' => 'reaction_link',
      '#content' => [
        'link' => $link,
      ],
      '#reaction' => $reaction_type,
      '#action' => $action,
      '#count' => $count,
    ];
  }

  /**
   * Set emoji reaction session cookie.
   */
  public function setCookie($session_id) {
    setcookie('reactions_session', $session_id, time() + (86400 * 7), '/');

    // EmojiReactions uses Ajax and page isn't reloading.
    // That is why we manually set $_COOKIE
    // because once the cookies have been set,
    // they can be accessed only on the next page load.
    $_COOKIE['reactions_session'] = $session_id;
  }

  /**
   * Checks if token matches with provided dom element id.
   *
   * @return bool
   *   Returns true if token is valid or false otherwise.
   */
  public function validateToken($token, $html_id) {
    return $this->csrfTokenGenerator->validate($token, $html_id);
  }

  /**
   * Get Emoji Reaction session cookie.
   */
  public function getCookie() {
    return $_COOKIE['reactions_session'] ?? FALSE;
  }

  /**
   * Remove previeously setted colkie cookie.
   *
   * @void
   */
  public function removeCookie() {
    setcookie('reactions_session', '', time() - 3600);
  }

  /**
   * Get current user session id.
   */
  public function getUserSessionId(AccountInterface $user = NULL) {
    $session_id = uniqid();
    if (empty($user)) {
      $user = $this->account;
    }
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
   * Get the reaction type id from the name.
   *
   * @param string
   *   Reaction type name.
   *
   * @return string|bool
   *   The reaction type id or false if no reaction_type found.
   */
  public function getReactionTypeIdFromName(string $type_name) {
    $type_ids = $this->entityTypeManager
      ->getStorage('emoji_reaction_type')
      ->getQuery()
      ->condition('name', $type_name)
      ->execute();

    return reset($type_ids);
  }

  /**
   * Check if there is an existing reaction for entity and user.
   *
   * @return \Drupal\emoji_reactions\Entity\EmojiReactionInterface|bool
   *   Returns the current emoji reaction entity or FALSE if not setted.
   */
  public function check($entity, AccountInterface $account = NULL, EmojiReactionType $reaction_type = NULL) {
    if (!$entity) {
      return FALSE;
    }

    if (empty($account)) {
      $account = $this->account;
    }

    $storage = $this->entityTypeManager->getStorage('emoji_reaction');
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

    // Set reaction type query parameter if specified.
    if (!empty($reaction_type)) {
      $query->condition('reaction_type_id', $reaction_type->id());
    }

    $reactions = $query->execute();

    if (!empty($reactions)) {
      return $storage->load(reset($reactions));
    }

    return FALSE;
  }

  /**
   * Set reaction.
   */
  public function setReaction(string $reaction_type, EntityInterface $entity, AccountInterface $account = NULL) {

    if (empty($account)) {
      $account = $this->account;
    }

    $session_id = $this->getUserSessionId($account);
    $reaction = $this->check($entity, $account);

    // if ($reaction === FALSE) {
      // Create new reaction.
      $values = [
        'user_id' => $account->id(),
        'target_entity_type' => $entity->getEntityTypeId(),
        'target_entity_id' => $entity->id(),
        'session_id' => $session_id,
      ];

      /** @var EmojiReaction $reaction */
      $reaction = $this->entityTypeManager
        ->getStorage('emoji_reaction')
        ->create($values);
      
      $reaction->setTypeName($reaction_type);
      
      $reaction->save();
    // }
    // elseif ($reaction->getTypeName() !== $reaction_type) {
    //   $reaction->setTypeName($reaction_type);
    //   $reaction->save();
    // }
    // else {
    //   // If reaction of type $reaction_type already exists, do nothing.
    // }

    return $reaction;

  }

  /**
   * Removes a reaction from an entity.
   */
  public function removeReaction(string $type_name, EntityInterface $entity, AccountInterface $account = NULL) {
    if (empty($account)) {
      $account = $this->account;
    }

    $type = null;

    /** @var \Drupal\emoji_reactions\Entity\EmojiReactionType[] $types */
    $types = $this->entityTypeManager->getStorage('emoji_reaction_type')
      ->loadByProperties([
        'name' => $type_name,
      ]);
    
    if (!empty($types)) {
      $type = reset($types);
    }

    $reaction = $this->check($entity, $account, $type);

    if ($reaction !== FALSE) {
      $reaction->delete();
    }
    return $reaction;
  }

  /**
   * Remove reaction.
   */
  public function removeAllFromEntity(EntityInterface $entity, AccountInterface $account = NULL) {

    // Get entity related reactions.
    $storage = $this->entityTypeManager->getStorage('emoji_reaction');
    $type = $entity->getEntityTypeId();
    $query = $storage->getQuery()
      ->condition('target_entity_type', $type)
      ->condition('target_entity_id', $entity->id());

    // Is user is specified, limit to user's reaction.
    if (!empty($account)) {
      $query->condition('user_id', $account->id());
    }
    $reactions = $query->execute();
    $entities = $storage->loadMultiple($reactions);

    // Remove all entity reactions.
    $storage->delete($entities);
  }

  /**
   *
   */
  public function removeAllFromUser(AccountInterface $account) {

    // Find all emoji_reactions from a user.
    $storage = $this->entityTypeManager->getStorage('emoji_reaction');
    $reactions = $storage->getQuery()
      ->condition('user_id', $account->id())
      ->execute();

    if (!empty($reactions)) {
      $action = \Drupal::config('emoji_reactions.settings')
        ->get('after_owner_deletion');

      /** @var \Drupal\emoji_reactions\Entity\EmojiReactionInterface[] $reactions */
      $reactions = $storage->loadMultiple($reactions);

      // Set owner to anonymous.
      if ($action == 'set_to_anonymous') {
        foreach ($reactions as $reaction) {
          $reaction->setOwnerId(0)
            ->save();
        }
      }
      else {
        // Delete all user's reactions.
        $storage->delete($reactions);
      }
    }
  }

}
