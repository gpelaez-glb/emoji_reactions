<?php

namespace Drupal\emoji_reactions\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;
use InvalidArgumentException;

/**
 * Defines the Emoji Reaction entity.
 *
 * @ingroup emoji_reaction
 *
 * @ContentEntityType(
 *   id = "emoji_reaction",
 *   label = @Translation("Emoji Reaction"),
 *   handlers = {
 *     "views_data" = "Drupal\emoji_reactions\Entity\EmojiReactionViewsData",
 *   },
 *   base_table = "emoji_reactions",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "reaction_type" = "reaction_type",
 *   },
 *   links = {},
 * )
 */
class EmojiReaction extends ContentEntityBase implements EmojiReactionInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type, $bundle = FALSE, $translations = []) {
    if (isset($values['target_entity_id'])) {
      $values['target_entity'] = $values['target_entity_id'];
    }
    if (isset($values['reaction_type'])) {
      $values['emoji_reaction_type'] = $values['reaction_type'];
    }
    parent::__construct($values, $entity_type, $bundle, $translations);
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += ['user_id' => \Drupal::currentUser()->id()];
  }

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
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityId() {
    return $this->get('target_entity_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetEntityId($target_entity_id) {
    $this->set('target_entity_id', $target_entity_id);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityType() {
    return $this->get('target_entity_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetEntityType($target_entity_type) {
    $this->set('target_entity_type', $target_entity_type);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntity() {
    return $this->entityTypeManager()
      ->getStorage($this->getEntityType())
      ->load($this->getTargetEntityId());
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    $reaction_type_id = $this->get('reaction_type_id')->getValue()[0]['target_id'];

    /** @var EmojiReactionType $reaction_type */
    return $this->entityTypeManager()
      ->getStorage('emoji_reaction_type')
      ->load($reaction_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeName() {
    $type = $this->getType();
    if (empty($type)) {
      return '';
    }
    return $type->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTypeName(string $type_name) {
    $type_ids = $this->entityTypeManager()
      ->getStorage('emoji_reaction_type')
      ->getQuery()
      ->condition('name', $type_name)
      ->execute();

    if (empty($type_ids)) {
      throw new InvalidArgumentException(
        'Invalid reaction type name: ' . $type_name,
      );
    }
    $this->set('reaction_type_id', reset($type_ids));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionId() {
    return $this->get('session_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSessionId($id) {
    $this->set('session_id', $id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /* @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the EmojiReaction entity.'))
      ->setReadOnly(TRUE);
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the EmojiReaction entity.'))
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner ID'))
      ->setDescription(t('The user ID, EmojiReaction entity owner.'))
      ->setSettings([
        'target_type' => 'user',
        'default_value' => 0,
      ]);

    $fields['reaction_type_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Reaction Type ID'))
      ->setDescription(t('The EmojiReactionType ID.'))
      ->setRequired(TRUE)
      ->setSettings([
        'target_type' => 'emoji_reaction_type',
      ]);

    $fields['reaction_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Target Entity Type'))
      ->setDescription(t('The target entity type.'))
      ->setComputed(TRUE);

    $fields['target_entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Target Entity Type'))
      ->setDescription(t('The target entity type.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ]);

    $fields['target_entity_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Target Entity ID'))
      ->setRequired(TRUE)
      ->setDescription(t('The target entity ID.'));

    $fields['target_entity'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Target Entity'))
      ->setDescription(t('The target entity.'))
      ->setComputed(TRUE);

    $fields['session_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Session ID'))
      ->setDescription(t('The User session ID.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    return $fields;
  }

}
