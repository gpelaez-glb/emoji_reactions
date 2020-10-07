<?php

namespace Drupal\emoji_reactions\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Emoji Reaction entity.
 *
 * @ingroup emoji_reaction
 *
 * @ContentEntityType(
 *   id = "emoji_reaction_type",
 *   label = @Translation("Emoji Reaction Type"),
 *   handlers = {
 *     "views_data" = "Drupal\emoji_reactions\Entity\EmojiReactionTypeViewsData",
 *   },
 *   base_table = "emoji_reaction_types",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "name" = "reaction_name",
 *   },
 *   links = {},
 * )
 */
class EmojiReactionType extends ContentEntityBase {

  const EMOJI_REACTIONS_ANGRY = 'angry';
  const EMOJI_REACTIONS_HAHA = 'haha';
  const EMOJI_REACTIONS_LIKE = 'like';
  const EMOJI_REACTIONS_LOVE = 'love';
  const EMOJI_REACTIONS_SAD = 'sad';
  const EMOJI_REACTIONS_WOW = 'wow';
  const EMOJI_REACTIONS_YAY = 'yay';

  const EMOJI_REACTIONS_DEFAULTS = [
    self::EMOJI_REACTIONS_LIKE,
    self::EMOJI_REACTIONS_LOVE,
    self::EMOJI_REACTIONS_YAY,
    self::EMOJI_REACTIONS_HAHA,
    self::EMOJI_REACTIONS_WOW,
    self::EMOJI_REACTIONS_SAD,
    self::EMOJI_REACTIONS_ANGRY,
  ];

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * Gets reaction type name.
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * Gets reaction type icon.
   *
   * @return array
   *   Reaction type icon renderable array.
   */
  public function getReactionTypeIcon() {
    $use_animated_icon = $this->get('use_animated_icon')->value;
    if ($use_animated_icon) {
      // TODO: Build renderable array for animated emoji.
      return [
        '#theme' => 'reaction_emoji',
        '#reaction' => $this->get('animated_icon')->value,
        '#attached' => [
          'library' => 'emoji_reactions/animated_emoji',
        ],
      ];
    }
    else {
      // TODO: Build a renderable array for image field.
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /* @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the EmojiReactionType entity.'))
      ->setReadOnly(TRUE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('The sorting weight of the EmojiReactionType entity.'))
      ->setDefaultValue(0);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the EmojiReactionType entity.'))
      ->setReadOnly(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Reaction type name.'))
      ->setDescription(t('The reaction type name.'))
      ->setSettings([
        'max_length' => 12,
        'text_processing' => 0,
      ])
      ->addConstraint('UniqueField', []);

    $fields['use_animated_icon'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Use animated icon for reaction.'))
      ->setDescription(t('Use animated icon on reaction button.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 0,
      ])
      ->setDefaultValue(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['animated_icon'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Reaction type animated icon.'))
      ->setDescription(t('The reaction type animated icon.'))
      ->setSettings([
        'allowed_values' => array_combine(self::EMOJI_REACTIONS_DEFAULTS, self::EMOJI_REACTIONS_DEFAULTS),
      ])
      ->setDefaultValue(self::EMOJI_REACTIONS_DEFAULTS[0])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $$fields['image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Image'))
      ->setDescription(t('Image field'))
      ->setSettings([
        'file_directory' => 'emoji_reaction_types',
        'alt_field_required' => FALSE,
        'file_extensions' => 'png jpg jpeg gif',
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'default',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'label' => 'hidden',
        'type' => 'image_image',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    return $fields;
  }

}
