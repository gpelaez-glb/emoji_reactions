<?php

/**
 * @file
 * Contains emoji_reactions.views.inc.
 */

/**
 * Implements hook_views_data().
 */
function emoji_reactions_views_data() {
  $data['emoji_reactions']['table']['group'] = t('Emoji Reactions!');
  $data['emoji_reactions']['table']['join'] = [
    '#global' => [],
  ];

  $data['emoji_reactions']['reactions_target_entity_title_views_field'] = [
    'title' => t('Target Entity Title'),
    'help' => t('Providing target entity title that user has liked.'),
    'field' => [
      'id' => 'reactions_target_entity_title_views_field',
    ],
  ];

  $data['emoji_reactions']['reactions_target_entity_view_views_field'] = [
    'title' => t('Target Entity View'),
    'help' => t('Providing target entity view.'),
    'field' => [
      'id' => 'reactions_target_entity_view_views_field',
    ],
  ];

  return $data;
}
