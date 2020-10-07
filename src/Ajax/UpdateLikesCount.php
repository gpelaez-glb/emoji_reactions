<?php

namespace Drupal\emoji_reactions\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class UpdateLikesCount.
 */
class UpdateLikesCount implements CommandInterface {

  /**
   * Render custom ajax command.
   *
   * @return ajax
   *   Command function.
   */
  public function render() {
    return [
      'command' => 'updateLikesCount',
      'message' => 'My Awesome Message',
    ];
  }

}
