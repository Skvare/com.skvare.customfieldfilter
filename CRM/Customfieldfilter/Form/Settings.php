<?php

/**
 * @file
 * Admin form for Custom Field Filter extension settings
 */

use CRM_Customfieldfilter_ExtensionUtil as E;

/**
 * Admin Settings Form for Custom Field Filter Configuration
 */
class CRM_Customfieldfilter_Form_Settings extends CRM_Core_Form {

  /**
   * Build the admin settings form
   */
  public function buildQuickForm() {

    // Set form title
    $this->setTitle(E::ts('Custom Field Filter Settings'));

    // Get all custom groups
    $customGroups = $this->getCustomGroups();
    //echo '<pre>'; print_r($customGroups);exit;
    // Create main fieldset
    $this->assign('customGroups', $customGroups);

    // Add form elements for each custom group
    foreach ($customGroups as $groupId => $groupData) {

      // Add checkbox to enable/disable entire group for filtering
      $this->addElement('checkbox',
        "enable_group_{$groupId}",
        E::ts('Enable filtering for %1', [1 => $groupData['title']])
      );

      // Add individual field selection for each group
      if (!empty($groupData['fields'])) {
        $fieldOptions = [];
        foreach ($groupData['fields'] as $fieldId => $fieldData) {
          $fieldOptions[$fieldId] = $fieldData['label'] . ' (' . $fieldData['data_type'] . ')';
        }

        $this->addElement('select',
          "fields_group_{$groupId}",
          E::ts('Select fields to enable for filtering in %1', [1 => $groupData['title']]),
          $fieldOptions,
          [
            'multiple' => 'multiple',
            'class' => 'crm-select2 huge',
            'placeholder' => E::ts('- Select Fields -')
          ]
        );
      }

      // Add position/order setting
      $this->addElement('text',
        "weight_group_{$groupId}",
        E::ts('Display order for %1', [1 => $groupData['title']]),
        ['size' => 3, 'class' => 'four']
      );
    }

    $this->addElement('checkbox',
      'collapsible_filters',
      E::ts('Make filter sections collapsible')
    );


    // Add standard form buttons
    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Save Settings'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => E::ts('Cancel'),
      ],
    ]);

    // Add validation rules
    $this->addFormRule(['CRM_Customfieldfilter_Form_Settings', 'formRule']);
    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames(): array {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = [];
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

  /**
   * Set default values for the form
   */
  public function setDefaultValues() {
    $defaults = [];

    // Get current settings from database or use defaults
    $settings = Civi::settings()->get('customfieldfilter_settings') ?: [];

    // Set defaults for global settings
    $defaults['collapsible_filters'] = $settings['collapsible_filters'] ?? 0;

    // Set defaults for each custom group
    $customGroups = $this->getCustomGroups();
    foreach ($customGroups as $groupId => $groupData) {
      $defaults["enable_group_{$groupId}"] = $settings["enable_group_{$groupId}"] ?? 0;
      $defaults["fields_group_{$groupId}"] = $settings["fields_group_{$groupId}"] ?? [];
      $defaults["weight_group_{$groupId}"] = $settings["weight_group_{$groupId}"] ?? $groupId;
    }

    return $defaults;
  }

  /**
   * Process form submission
   */
  public function postProcess() {
    $values = $this->exportValues();

    // Prepare settings array
    $settings = [];

    // Global settings
    $settings['collapsible_filters'] = $values['collapsible_filters'] ?? 0;


    // Group-specific settings
    $customGroups = $this->getCustomGroups();
    foreach ($customGroups as $groupId => $groupData) {
      $settings["enable_group_{$groupId}"] = $values["enable_group_{$groupId}"] ?? 0;
      $settings["fields_group_{$groupId}"] = $values["fields_group_{$groupId}"] ?? [];
      $settings["weight_group_{$groupId}"] = (int)($values["weight_group_{$groupId}"] ?? $groupId);
    }

    // Save settings
    Civi::settings()->set('customfieldfilter_settings', $settings);

    // Clear any relevant caches
    //CRM_Core_BAO_Cache::clearGroup('customfieldfilter');

    CRM_Core_Session::setStatus(
      E::ts('Custom Field Filter settings have been saved.'),
      E::ts('Settings Saved'),
      'success'
    );
  }

  /**
   * Form validation rules
   */
  public static function formRule($values) {
    $errors = [];

    // Validate weight values
    foreach ($values as $key => $value) {
      if (strpos($key, 'weight_group_') === 0 && !empty($value)) {
        if (!is_numeric($value)) {
          $errors[$key] = E::ts('Display order must be a number.');
        }
      }
    }

    return $errors;
  }

  /**
   * Get all custom groups and their fields
   */
  private function getCustomGroups() {
    $customGroups = [];

    try {
      // Get custom groups
      $groupResult = civicrm_api3('CustomGroup', 'get', [
        'sequential' => 1,
        'is_active' => 1,
        'is_multiple' => 1,
        'style' => "Tab with table",
        'options' => ['limit' => 0, 'sort' => 'weight ASC, title ASC'],
      ]);

      foreach ($groupResult['values'] as $group) {
        $groupId = $group['id'];

        // Get custom fields for this group
        $fieldResult = civicrm_api3('CustomField', 'get', [
          'sequential' => 1,
          'custom_group_id' => $groupId,
          'is_active' => 1,
          'options' => ['limit' => 0, 'sort' => 'weight ASC, label ASC'],
        ]);

        $fields = [];
        foreach ($fieldResult['values'] as $field) {
          $fields[$field['id']] = [
            'label' => $field['label'],
            'data_type' => $field['data_type'],
            'html_type' => $field['html_type'],
            'option_group_id' => $field['option_group_id'] ?? NULL,
          ];
        }

        $customGroups[$groupId] = [
          'title' => $group['title'],
          'table_name' => $group['table_name'],
          'extends' => $group['extends'],
          'style' => $group['style'] ?? 'Inline',
          'collapse_display' => $group['collapse_display'] ?? 0,
          'fields' => $fields,
        ];
      }

    }
    catch (CiviCRM_API3_Exception $e) {
      CRM_Core_Error::debug_log_message('Error getting custom groups: ' . $e->getMessage());
    }

    return $customGroups;
  }
}
