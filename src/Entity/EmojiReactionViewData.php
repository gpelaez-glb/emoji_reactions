<?php

namespace Drupal\emoji_reactions\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Implements EntityViewDataInterface.
 */
class EmojiReactionViewData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    return $data;
  }

}
