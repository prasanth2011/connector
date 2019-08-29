<?php

namespace Drupal\viewsreference\Plugin\Field\FieldType;

use Drupal\views\Views;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\PreconfiguredFieldUiOptionsInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'viewsreference' entity field type.
 *
 * The target type for viewsreference fields should always be 'view'.
 *
 * @FieldType(
 *   id = "viewsreference",
 *   label = @Translation("Views reference"),
 *   description = @Translation("A field reference to a view."),
 *   category = @Translation("Reference"),
 *   default_widget = "viewsreference_autocomplete",
 *   default_formatter = "viewsreference_formatter",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 * )
 */
class ViewsReferenceItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'target_type' => 'view',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'plugin_types' => ['block' => 'block'],
      'preselect_views' => [],
      'enabled_settings' => [],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $properties['display_id'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Display Id'))
      ->setDescription(new TranslatableMarkup('The referenced display Id'));
    $properties['data'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Data'))
      ->setDescription(new TranslatableMarkup('Settings data for advanced use'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $target_type = $field_definition->getSetting('target_type');
    $target_type_info = \Drupal::entityTypeManager()->getDefinition($target_type);
    $schema['columns']['display_id'] = [
      'description' => 'The ID of the display.',
      'type' => 'varchar_ascii',
      // If the target entities act as bundles for another entity type,
      // their IDs should not exceed the maximum length for bundles.
      'length' => $target_type_info->getBundleOf() ? EntityTypeInterface::BUNDLE_MAX_LENGTH : 255,
    ];
    $schema['columns']['data'] = [
      'description' => 'Serialized data.',
      'type' => 'text',
      'size' => 'big',
    ];
    $schema['indexes']['display_id'] = ['display_id'];
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // Select widget has extra layer of items.
    if (isset($values['target_id']) && is_array($values['target_id'])) {
      $values['target_id'] = isset($values['target_id'][0]['target_id']) ? $values['target_id'][0]['target_id'] : NULL;
    }
    parent::setValue($values, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::fieldSettingsForm($form, $form_state);
    $settings = $this->getSettings();
    $preselect_views = isset($settings['preselect_views']) ? $settings['preselect_views'] : [];
    $default_plugins = isset($settings['plugin_types']) ? $settings['plugin_types'] : [];
    $enabled_settings = isset($settings['enabled_settings']) ? $settings['enabled_settings'] : [];
    $display_options = $this->getAllViewDisplayIds();
    $view_list = $this->getAllViewsNames();

    $form['plugin_types'] = [
      '#type' => 'checkboxes',
      '#options' => $display_options,
      '#title' => $this->t('View display plugins to allow'),
      '#default_value' => $default_plugins,
      '#weight' => 1,
    ];

    $form['preselect_views'] = [
      '#type' => 'checkboxes',
      '#title' => t('Preselect View Options'),
      '#options' => $view_list,
      '#default_value' => $preselect_views,
      '#weight' => 2,
    ];

    $enabled_settings_list = [];
    $viewsreference_plugin_manager = \Drupal::service('plugin.manager.viewsreference.setting');
    $plugin_definitions = $viewsreference_plugin_manager->getDefinitions();
    foreach ($plugin_definitions as $plugin_definition) {
      $enabled_settings_list[$plugin_definition['id']] = $plugin_definition['label'];
    }
    $form['enabled_settings'] = [
      '#type' => 'checkboxes',
      '#title' => t('Enable extra settings'),
      '#options' => $enabled_settings_list,
      '#default_value' => $enabled_settings,
      '#weight' => 3,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function getPreconfiguredOptions() {
    return [];
  }

  /**
   * Get all views display IDs.
   *
   * @return array
   *   An array of view display IDs keyed bu plugin name.
   */
  protected function getAllViewDisplayIds() {
    $types = Views::pluginList();
    $options = [];
    foreach ($types as $key => $type) {
      if ($type['type'] === 'display') {
        $options[str_replace('display:', '', $key)] = $type['title']->render();
      }
    }
    return $options;
  }

  /**
   * Get all enabled view names.
   *
   * @return array
   *   An array of enabled view names keyed by view ID.
   */
  protected function getAllViewsNames() {
    $views = Views::getEnabledViews();
    $options = [];
    foreach ($views as $view) {
      $options[$view->get('id')] = $view->get('label');
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    // Avoid loading the entity by first checking the 'display_id'.
    if ($this->display_id === NULL || $this->display_id == '') {
      return TRUE;
    }
    return parent::isEmpty();
  }

}
