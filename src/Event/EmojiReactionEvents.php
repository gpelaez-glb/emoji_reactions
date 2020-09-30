<?php

namespace Drupal\emoji_reactions\Entity;

/**
 *
 */
final class EmojiReactionEvents {

  /**
   * Name of the event fired when a user reacts to an entity.
   *
   * This event allows modules to perform an action whenever a user reacts
   * an entity. The event listener method receives an instance
   * of \Drupal\emoji_reaction\Event\EmojiReactionEvent.
   *
   * @Event
   *
   * @see \Drupal\emoji_reactions\Event\EmojiReactionEvent
   *
   * @var string
   */
  const REACT = 'emoji_reaction.react';

  /**
   * Name of the event fired when a user removes a reaction from an entity.
   *
   * This event allows modules to perform an action whenever a user removes
   * a reaction to an entity. The event listener method receives an instance
   * of \Drupal\emoji_reactions\Event\EmojiReactionEvent.
   *
   * @Event
   *
   * @see \Drupal\emoji_reactions\Event\EmojiReactionEvent
   *
   * @var string
   */
  const REMOVE = 'emoji_reaction.remove';

}
