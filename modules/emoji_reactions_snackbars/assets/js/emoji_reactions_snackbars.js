(function name($, Drupal, Snackbar) {

  'use strict';

  Drupal.SnackbarController = {};

  Drupal.SnackbarController.showMessage = function(message) {
    Snackbar.show({
      //Set the position
      pos: 'top-right',
      showAction: true,
      text: message
    });
  }

  Drupal.AjaxCommands.prototype.showSnackbar = function (
    ajax,
    response,
    status
  ) {
    if (typeof response.data !== "undefined") {
      Drupal.SnackbarController.showMessage(
        response.data.message
      );
    }
  };
  
})(jQuery, Drupal, Snackbar)