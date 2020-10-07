<?php

namespace Drupal\emoji_reactions\Event;

use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\emoji_reactions\Entity\EmojiReactionType;
use Symfony\Component\EventDispatcher\Event;

/**
 * EmojiReactionEvent class.
 */
class EmojiReactionEvent extends Event {

  /**
   * The target entity.
   *
   * @var \Drupal\Core\Entity\EntityBase
   */
  protected $entity;

  /**
   * The reaction Type.
   *
   * @var \Drupal\Core\Entity\EntityBase
   */
  protected $reactionType;

  /**
   * The user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Constructs an emoji reaction toggle event object.
   *
   * @param \Drupal\Core\Entity\EntityBase $entity
   *   The target entity.
   * @param \Drupal\emoji_reactions\Entity\EmojiReactionType $reaction_type
   *   Emoji Reaction Type.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User account related to EmojiReaction.
   */
  public function __construct(EntityBase $entity, EmojiReactionType $reaction_type, AccountInterface $account = NULL) {
    $this->entity = $entity;
    $this->reactionType = $reaction_type;
    $this->account = $account;
  }

  /**
   * Get the reaction Type.
   *
   * @return \Drupal\Core\Entity\EntityBase
   *   The reaction related entity.
   */
  public function getReactionType() {
    return $this->reactionType;
  }

  /**
   * Set the reaction Type.
   *
   * @param \Drupal\Core\Entity\EntityBase $reactionType
   *   The reaction Type.
   *
   * @return self
   *   The Emoji Reaction Event instance.
   */
  public function setReactionType(EntityBase $reactionType) {
    $this->reactionType = $reactionType;

    return $this;
  }

  /**
   * Get the user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The user attached to the reaction.
   */
  public function getAccount() {
    return $this->account;
  }

  /**
   * Set the user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user.
   *
   * @return self
   *   The Emoji Reaction Event instance.
   */
  public function setAccount(AccountInterface $account) {
    $this->account = $account;

    return $this;
  }

  /**
   * Get the target entity.
   *
   * @return \Drupal\Core\Entity\EntityBase
   *   Get Related Entity.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Set the target entity.
   *
   * @param \Drupal\Core\Entity\EntityBase $entity
   *   The target entity.
   *
   * @return self
   *   The Emoji Reaction Event instance.
   */
  public function setEntity(EntityBase $entity) {
    $this->entity = $entity;

    return $this;
  }

}
