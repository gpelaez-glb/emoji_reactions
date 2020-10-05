<?php

namespace Drupal\emoji_reactions\EventSubscriber;

use Drupal\Core\Entity\EntityTypeEvent;
use Drupal\Core\Entity\EntityTypeEvents;
use Drupal\emoji_reactions\Entity\EmojiReactionType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class EmojiReactionsEntityEventSubscriber.
 */
class EmojiReactionsEntityEventSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new EmojiReactionsEntityEventSubscriber object.
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[EntityTypeEvents::CREATE] = ['onEntityTypeCreate'];

    return $events;
  }

  /**
   * This method is called when the EntityTypeEvents::CREATE is dispatched.
   *
   * @param \Drupal\Core\Entity\EntityTypeEvent $event
   *   The dispatched event.
   */
  public function onEntityTypeCreate(EntityTypeEvent $event) {
    $entity_type = $event->getEntityType();
    if ($entity_type->id() == 'emoji_reaction_type') {
      $this->createDefaultReactionTypes();
      \Drupal::messenger()->addMessage('Event EntityTypeEvents::CREATE thrown by Subscriber in module emoji_reactions.', 'status', TRUE);

    }
  }

  /**
   *
   */
  private function createDefaultReactionTypes() {
    foreach (EMOJI_REACTIONS_DEFAULTS as $reaction_name) {
      $reaction_type = EmojiReactionType::create([
        'name' => $reaction_name,
        'use_animated_icon' => TRUE,
        'animated_icon' => $reaction_name,
      ]);
      $reaction_type->save();
    }
  }

}
