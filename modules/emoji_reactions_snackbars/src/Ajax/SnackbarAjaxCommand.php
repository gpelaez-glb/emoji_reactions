<?php

namespace Drupal\emoji_reactions_snackbars\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class SnackbarAjaxCommand.
 */
class SnackbarAjaxCommand implements CommandInterface {

  /**
   * The toast message to be displayed.
   *
   * @var string
   */
  protected $message;

  /**
   * Constructor for Toast Command.
   */
  public function __construct($message = '', $type = 'info') {
    $this->message = $message;
  }

  /**
   * Render custom ajax command.
   *
   * @return ajax
   *   Command function.
   */
  public function render() {
    return [
      'command' => 'showSnackbar',
      'data' => [
        'message' => $this->message,
      ],
    ];
  }

}
