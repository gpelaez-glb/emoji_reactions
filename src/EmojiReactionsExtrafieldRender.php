<?php

namespace Drupal\emoji_reactions\Service;

use Drupal\Core\Entity\EntityBase;
use Drupal\extrafield_views_integration\lib\ExtrafieldRenderClassInterface;

/**
 *
 */
class EmojiReactionsExtrafieldRender implements ExtrafieldRenderClassInterface {

  /**
   * @inheritDoc
   */
  public static function render(EntityBase $entity) {
    /** @var EmojiReactionsManager $emoji_reactions_manager */
    $emoji_reactions_manager = \Drupal::service('emoji_reactions.manager');
    return $emoji_reactions_manager->getLinksByEntity($entity);
  }

}
