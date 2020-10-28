<?php

namespace Drupal\emoji_reactions_snackbars\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Replace the likeit module default routes with some custom routes.
    if ($route = $collection->get('emoji_reactions.react')) {
      $route->setDefaults([
        '_controller' => '\Drupal\emoji_reactions_snackbars\Controller\EmojiReactionsSnackbarController:react',
      ]);
    }
    if ($route = $collection->get('emoji_reactions.remove')) {
      $route->setDefaults([
        '_controller' => '\Drupal\emoji_reactions_snackbars\Controller\EmojiReactionsSnackbarController:remove',
      ]);
    }
  }

}
