<?php

namespace Drupal\emoji_reactions\Service;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection as DatabaseConnection;
use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\emoji_reactions\Access\CsrfTokenGenerator;
use Drupal\emoji_reactions\Controller\EmojiReactionsController;
use Drupal\emoji_reactions\Entity\EmojiReactionType;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class EmojiReactionsManager.
 */
class EmojiReactionsManager {

  const REACTION_TYPE_ADD = 0;
  const REACTION_TYPE_REMOVE = 1;

  /**
   * EmojiReactions Settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Current RequestStack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $stack;

  /**
   * Current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

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
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $stack, AccountProxyInterface $account, DatabaseConnection $database, EntityTypeManagerInterface $entity_type_manager, CsrfTokenGenerator $csrf_token_generator) {
    $this->config = $config_factory->get('emoji_reactions.settings');
    $this->stack = $stack;
    $this->account = $account;
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->csrfTokenGenerator = $csrf_token_generator;
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

    // Check if reactions should be applied to this entity type.
    $target_entities = $this->config->get('target_entities');
    if (!empty($target_entities)) {
      $target = $entity->getEntityTypeId() . ':' . $entity->bundle();
      if (!in_array($target, $target_entities)) {
        return '';
      }
    }

    /*
     * TODO:
     * [x] Get Reactions Stats.
     * [] Check if user reacted to the entity.
     */
    $html_id = uniqid('em-reactions-' . $entity->id());
    $html_id = Html::getId($html_id);

    $content = [];

    if (EmojiReactionsController::checkAccess('view', $this->account)) {
      $content['stats'] = $this->getStats($entity);
    }

    // Initialize active type variable.
    $active_button = NULL;

    // Iterate available emoji reaction types to build reaction links.
    $emoji_reactions = [];
    /** @var \Drupal\emoji_reactions\Entity\EmojiReactionType[] $types */
    $types = $this->entityTypeManager->getStorage('emoji_reaction_type')
      ->loadMultiple();
    foreach ($types as $type) {
      $current_reaction = $this->check($entity, NULL, $type);
      $action = $current_reaction == FALSE ? 'react' : 'remove';
      if (EmojiReactionsController::checkAccess($action, $this->account)) {
        $button = $this->buildReactionLink($entity, $type, $html_id, $action);
        if ($current_reaction !== FALSE) {
          $active_button = $button;
        }
        else {
          $emoji_reactions[] = $button;
        }
      }
    }

    $content['reaction_button'] = [
      '#theme' => 'reactions_button',
      '#reactions' => $emoji_reactions,
      '#button' => $active_button,
    ];

    $cache_tag = 'reactions_' . $entity->getEntityTypeId() . '_' . $entity->bundle() . '_' . $entity->id();
    
    return [
      '#theme' => 'reactions',
      '#content' => $content,
      '#attributes' => [
        'id' => $html_id,
      ],
      '#cache' => [
        'contexts' => [ 
          // The "current user" is used above, which depends on the request, 
          // so we tell Drupal to vary by the 'user' cache context.
          'user', 
        ],
        'tags' => [
          $cache_tag,
        ],
      ], 
    ];
  }

  /**
   * Gets a new csrf token for the element id.
   *
   * @param string $html_id
   *   Dom element id.
   *
   * @return string
   *   Generates a token based on $value, the token seed, and the private key.
   */
  public function getToken(string $html_id) {
    return $this->csrfTokenGenerator->get($html_id);
  }

  /**
   * Builds the reaction button renderable array.
   */
  public function buildReactionLink(EntityBase $entity, EmojiReactionType $reaction_type, string $html_id, string $action = 'react') {

    $title = [];
    $title[] = $reaction_type->getReactionTypeIcon();
    if ($action == 'remove') {
      $title[] = [
        '#markup' => "<span>Remove {$reaction_type->getName()} reaction.</span>",
      ];
    }

    $target = $entity->getEntityTypeId() . ':' . $entity->bundle();

    $url = Url::fromRoute('emoji_reactions.' . $action, [
      'reaction_name' => $reaction_type->getName(),
      'target' => $target,
      'id' => $entity->id(),
      'html_id' => $html_id,
      'token' => $this->getToken($html_id),
    ]);

    $link = [
      '#type' => 'link',
      '#title' => render($title),
      '#url' => $url,
    ];
    $link['#attributes']['title'] = $title;
    $link['#attributes']['class'] = ['use-ajax', 'emoji-reaction'];

    if ($action == 'remove') {
      $link['#attributes']['class'][] = 'active';
    }

    return [
      '#theme' => 'reaction_link',
      '#content' => [
        'link' => $link,
      ],
      '#reaction' => $reaction_type,
      '#action' => $action,
    ];
  }

  /**
   * Gets the number of reactions of a type for an entity.
   */
  public function getStats(EntityBase $entity) {

    $query = $this->database->query(
      "SELECT MAX({emoji_reactions}.id) AS 'id', {emoji_reactions}.target_entity_id AS 'target', {emoji_reactions}.reaction_type_id AS 'type_id',
      MAX({emoji_reaction_types}.name) AS 'type_name', COUNT({emoji_reactions}.id) AS 'count' FROM {emoji_reactions} JOIN {emoji_reaction_types} ON {emoji_reaction_types}.id = {emoji_reactions}.reaction_type_id
      WHERE {emoji_reactions}.target_entity_id = :target GROUP BY {emoji_reactions}.target_entity_id, {emoji_reactions}.reaction_type_id", [
        ':target' => $entity->id(),
      ]);

    $result = $query->fetchAllAssoc('id');
    $icons = [];
    $count = 0;
    foreach ($result as $value) {
      $icons[] = [
        'icon' => [
          '#theme' => 'reaction_emoji',
          '#animate' => FALSE,
          '#reaction' => $value->type_name,
          '#attributes' => [
            'class' => [
              'emoji-icon',
            ],
          ],
        ],
      ];
      $count += intval($value->count);
    }

    if ($count == 0) {
      return '';
    }

    return [
      '#theme' => 'reactions_stats',
      '#icons' => $icons,
      '#count' => $count,
    ];

  }

  /**
   * Gets the number of reactions of a type for an entity.
   */
  public function getCount(EntityBase $entity, EmojiReactionType $reaction_type = NULL) {
    $storage = $this->entityTypeManager->getStorage('emoji_reaction');

    $query = $storage->getQuery()
      ->condition('target_entity_id', $entity->id());

    // If reaction type name is given, add reaction to the query.
    if (!empty($reaction_type)) {
      $query->condition('reaction_type_id', $reaction_type->id());
    }

    return $query->count()->execute();

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
    $this->stack->getCurrentRequest()->cookies->set('reactions_sessions', $session_id);
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
    return $this->stack->getCurrentRequest()->cookies->get('reactions_sessions', FALSE);
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
   * @param string $type_name
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

    if ($reaction === FALSE) {
      // Create new reaction.
      $values = [
        'user_id' => $account->id(),
        'target_entity_type' => $entity->getEntityTypeId(),
        'target_entity_id' => $entity->id(),
        'session_id' => $session_id,
      ];

      /** @var \Drupal\emoji_reactions\Entity\EmojiReaction $reaction */
      $reaction = $this->entityTypeManager
        ->getStorage('emoji_reaction')
        ->create($values);

      $reaction->setTypeName($reaction_type);

      $reaction->save();
    }
    elseif ($reaction->getTypeName() !== $reaction_type) {
      $reaction->setTypeName($reaction_type);
      $reaction->save();
    }

    // Invalidate entity reaction cache tag.
    $cache_tag = 'reactions_' . $entity->getEntityTypeId() . '_' . $entity->bundle() . '_' . $entity->id();
    Cache::invalidateTags([$cache_tag]);

    return $reaction;

  }

  /**
   * Removes a reaction from an entity.
   */
  public function removeReaction(string $type_name, EntityInterface $entity, AccountInterface $account = NULL) {
    if (empty($account)) {
      $account = $this->account;
    }

    $type = NULL;

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

    // Invalidate entity reaction cache tag.
    $cache_tag = 'reactions_' . $entity->getEntityTypeId() . '_' . $entity->bundle() . '_' . $entity->id();
    Cache::invalidateTags([$cache_tag]);

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
   * Removes all reactions assigned to an user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User account to remove reactions.
   */
  public function removeAllFromUser(AccountInterface $account) {

    // Find all emoji_reactions from a user.
    $storage = $this->entityTypeManager->getStorage('emoji_reaction');
    $reactions = $storage->getQuery()
      ->condition('user_id', $account->id())
      ->execute();

    if (!empty($reactions)) {
      $action = $this->config
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
