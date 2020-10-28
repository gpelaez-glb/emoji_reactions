<?php

namespace Drupal\emoji_reactions_snackbars\Controller;

use Drupal\Core\Entity\EntityBase;
use Drupal\emoji_reactions\Controller\EmojiReactionsController;
use Drupal\emoji_reactions_snackbars\Ajax\SnackbarAjaxCommand;

/**
 * EmojiReactionsSnackbarController Class.
 *
 * Overrides and add custom ajax command con EmojiReactionsController response.
 */
class EmojiReactionsSnackbarController extends EmojiReactionsController {

  /**
   * Overrides EmojiReactionsController response.
   *
   * {@inheritdoc}
   */
  public function response(EntityBase $entity, $html_id) {
    /** @var \Drupal\Core\Ajax\AjaxResponse $response */
    $response = parent::response($entity, $html_id);

    $reaction = $this->emojiReactionsManager->check($entity, $this->currentUser);

    $title = $entity->getTitle();
    $message = '';
    if ($reaction == FALSE) {
      $message = "Reaction removed on \"$title\"";
    }
    else {
      $name = $reaction->getTypeName();
      $message = "You reacted \"$name\" on \"$title\"";
    }

    $response->addCommand(new SnackbarAjaxCommand($message));
    return $response;
  }

}
