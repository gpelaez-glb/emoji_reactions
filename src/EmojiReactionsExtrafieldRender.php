<?php

namespace Drupal\emoji_reactions\Service;

use Drupal\Core\Entity\EntityBase;
use Drupal\extrafield_views_integration\lib\ExtrafieldRenderClassInterface;

/**
 *
 */
class EmojiReactionsExtrafieldRenderer implements ExtrafieldRenderClassInterface {

  /**
   * @inheritDoc
   */
  public static function render(EntityBase $entity) {
    $config = \Drupal::config('emoji_reactions.settings');
    $target_entities = $config->get('target_entities');
    $emoji_reactions = '';

    if (!empty($target_entities)) {
      $target = $entity->getEntityTypeId() . ':' . $entity->bundle();
      if (in_array($target, $target_entities)) {
        $emoji_reactions = emoji_reactions_get_link($target, $entity->id());
      }
    }

    return $emoji_reactions;
  }

}
