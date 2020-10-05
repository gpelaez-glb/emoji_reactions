/**
 * @file
 */

(function ($, Drupal) {
  var initialized = false;

  function init(context, settings) {
    if (initialized) return;

    $(".emoji-reactions", context).each(function () {
      var $reaction = $(this);
      var $reaction_links = $reaction.find("ul.dropdown-menu");
      $reaction_links.hide();

      $(this).bind("mouseenter", function () {
        if (!$reaction_links.is(":visible")) {
          $reaction_links.slideDown();
        }
      });

      $reaction_links.bind("mouseleave", function () {
        if ($reaction_links.is(":visible")) {
          $reaction_links.slideUp();
        }
      });
      $(this)
        .find(".dropdown-toggle")
        .each(function () {
          $(this).bind("click", function () {
            $reaction_links.slideToggle();
          });
        });
    });
    initialized = true;
  }
  Drupal.behaviors.animatedEmoji = {
    attach: function (context, settings) {
      init(context, settings);
      // $(document).on("ready", function () {});
      // alert("Hola mundo");
    },
  };
})(jQuery, Drupal);
