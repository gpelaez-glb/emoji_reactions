<?php

namespace Drupal\emoji_reactions\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for EmojiReaction entity type.
 */
interface EmojiReactionInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the EmojiReaction creation timestamp.
   *
   * @return int
   *   Creation timestamp of the EmojiReaction.
   */
  public function getCreatedTime();

  /**
   * Sets the EmojiReaction creation timestamp.
   *
   * @param int $timestamp
   *   The EmojiReaction creation timestamp.
   *
   * @return \Drupal\emoji_reactions\Entity\EmojiReactionInterface
   *   The called EmojiReaction entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the EmojiReaction target entity id.
   *
   * @return int
   *   Target entity id.
   */
  public function getTargetEntityId();

  /**
   * Sets the EmojiReaction target entity id.
   *
   * @param int $target_entity_id
   *   Target entity id.
   *
   * @return \Drupal\emoji_reactions\Entity\EmojiReactionInterface
   *   The called EmojiReaction entity.
   */
  public function setTargetEntityId($target_entity_id);

  /**
   * Gets the EmojiReaction target entity type.
   *
   * @return string
   *   Target entity type.
   */
  public function getTargetEntityType();

  /**
   * Sets the EmojiReaction target entity type.
   *
   * @param string $target_entity_type
   *   Target entity type.
   *
   * @return \Drupal\emoji_reactions\Entity\EmojiReactionInterface
   *   The called EmojiReaction entity.
   */
  public function setTargetEntityType($target_entity_type);

  /**
   * Gets the EmojiReaction target entity.
   *
   * @return \Drupal\Core\Entity\Entity
   *   Target entity.
   */
  public function getTargetEntity();

  /**
   * Gets the EmojiReactionType.
   *
   * @return \Drupal\emoji_reactions\Entity\EmojiReactionType
   *   Emoji Reaction Type.
   */
  public function getType();

  /**
   * Sets the reaction type name.
   *
   * @param string $type_name
   *   The name of the reaction performed.
   *
   * @return \Drupal\emoji_reactions\Entity\EmojiReactionInterface
   *   The called EmojiReaction entity.
   *
   * @throws \InvalidArgumentException
   */
  public function setTypeName(string $type_name);

  /**
   * Gets the EmojiReaction's type name.
   *
   * @return string
   *   The name of the emoji reaction item.
   */
  public function getTypeName();

  /**
   * Gets the EmojiReaction owner session id.
   *
   * @return string
   *   Session id.
   */
  public function getSessionId();

  /**
   * Sets the EmojiReaction owner session id.
   *
   * @param string $id
   *   Session id.
   *
   * @return \Drupal\emoji_reactions\Entity\EmojiReactionInterface
   *   The called EmojiReaction entity.
   */
  public function setSessionId($id);

}
