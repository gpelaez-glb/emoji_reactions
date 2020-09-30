<?php

use Drupal\emoji_reactions\Entity\EmojiReactionInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 *
 */
class EmojiReactionEvent extends Event {

  /**
   * The target entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Constructs an emoji reaction toggle event object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The target entity.
   */
  public function __construct(EmojiReactionInterface $entity) {
    $this->entity = $entity;
  }

}
