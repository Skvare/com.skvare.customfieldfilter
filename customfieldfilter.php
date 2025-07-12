<?php

require_once 'customfieldfilter.civix.php';

use CRM_Customfieldfilter_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function customfieldfilter_civicrm_config(&$config): void {
  _customfieldfilter_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function customfieldfilter_civicrm_install(): void {
  _customfieldfilter_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function customfieldfilter_civicrm_enable(): void {
  _customfieldfilter_civix_civicrm_enable();
}

/**
 * Hook to modify custom field display
 */
function myextension_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Contact_Form_View') {
    // Add filter controls to the form
    $form->assign('customFieldFilters', TRUE);
  }
}

/**
 * Hook to alter template paths for custom field display
 */
function customfieldfilter_civicrm_alterTemplateFile($formName, &$form, $context, &$tplName) {
  if (strpos($tplName, 'CRM/Contact/Page/View/CustomDataFieldView.tpl') !== FALSE) {
    // Use our enhanced template
    //$tplName = 'CRM/Contact/Page/View/CustomDataFieldView.tpl';
  }
}

/**
 * API function to get filtered custom field data
 */
function civicrm_api3_custom_field_filter2($params) {
  $contactId = $params['contact_id'];
  $customGroupId = $params['custom_group_id'];
  $filterValue = CRM_Utils_Array::value('filter_value', $params, '');
  $filterField = CRM_Utils_Array::value('filter_field', $params, '');

  // Get custom group info
  $customGroup = civicrm_api3('CustomGroup', 'getsingle', [
    'id' => $customGroupId,
  ]);

  $tableName = $customGroup['table_name'];

  // Build query
  $sql = "SELECT * FROM {$tableName} WHERE entity_id = %1";
  $params_sql = [1 => [$contactId, 'Integer']];

  if ($filterValue && $filterField) {
    $sql .= " AND {$filterField} LIKE %2";
    $params_sql[2] = ['%' . $filterValue . '%', 'String'];
  }

  $dao = CRM_Core_DAO::executeQuery($sql, $params_sql);

  $results = [];
  while ($dao->fetch()) {
    $row = [];
    foreach (get_object_vars($dao) as $key => $value) {
      if (strpos($key, '_') !== 0) { // Skip private properties
        $row[$key] = $value;
      }
    }
    $results[] = $row;
  }

  return civicrm_api3_create_success($results);
}


/**
 * Hook implementation to modify contact summary page
 */
function customfieldfilter_civicrm_pageRun(&$page) {
  if (get_class($page) == 'CRM_Contact_Page_View_CustomData') {
    // Add our custom CSS and JS

    CRM_Core_Resources::singleton()->addStyleFile('com.skvare.customfieldfilter', 'css/custom-field-filter.css');
    CRM_Core_Resources::singleton()->addScriptFile('com.skvare.customfieldfilter', 'js/custom-field-filter.js');

    // Add contact ID and group info to the page for JavaScript access
    $contactId = $page->getVar('_contactId');
    if ($contactId) {
      $page->assign('customFieldFilters', TRUE);
      // Get all multi-value custom groups for this contact
      $customGroups = getMultiValueCustomGroups($contactId);
      //CRM_Core_Error::debug_var('Custom Groups', $customGroups);
      // Prepare filter data for each custom group
      foreach ($customGroups as $groupId => $groupInfo) {
        $filterData = getCustomFieldTabWithFilter($contactId, $groupId);
        //CRM_Core_Error::debug_var('$filterData $groupId', $groupId);
        //CRM_Core_Error::debug_var('$filterData Groups', $filterData);
        // Assign variables to template for each custom group
        $page->assign("customGroup_{$groupId}", $filterData['customGroup']);
        $page->assign("customFields_{$groupId}", $filterData['customFields']);
        $page->assign("filterFields_{$groupId}", $filterData['filterFields']);
        $page->assign("contactId_{$groupId}", $filterData['contactId']);
      }
      CRM_Core_Resources::singleton()->addVars('customFieldFilter', [
        'contactId' => $contactId,
        'customGroupId' => $groupId,
        'customGroups' => array_keys($customGroups),
        //'baseUrl' => CRM_Utils_System::url('civicrm/', '', TRUE, NULL, FALSE),
      ]);
    }
  }
}

/**
 * Hook to modify custom field display templates
 */
function customfieldfilter_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Contact_Form_View') {
    // Add filter controls to the form
    $form->assign('customFieldFilters', TRUE);
  }
}

/*
CALL FLOW EXPLANATION:
======================

1. Contact summary page loads → customfieldfilter_civicrm_pageRun() is triggered
2. pageRun hook calls getMultiValueCustomGroups() to find all multi-value custom groups
3. For each group found, getCustomFieldTabWithFilter() is called to prepare template data
4. Template variables are assigned: customGroup_{$groupId}, customFields_{$groupId}, etc.
5. Template renders with filter interface using the assigned variables
6. JavaScript initializes filter functionality for each custom group
7. User interactions trigger API calls to api/v3/CustomField/Filter.php
8. Results are displayed in real-time without page refresh

WHY getCustomFieldTabWithFilter() IS NOW CALLED:
- Called from customfieldfilter_civicrm_pageRun() for each multi-value custom group
- Prepares the data needed by the Smarty template
- Ensures template has access to custom field definitions and filter options
*/

/**
 * Template modification function - NOW PROPERLY CALLED FROM HOOK
 */
function getCustomFieldTabWithFilter($contactId, $customGroupId) {
  // Get custom group details
  $customGroup = civicrm_api3('CustomGroup', 'getsingle', [
    'id' => $customGroupId,
  ]);

  // Get custom fields for this group
  $customFields = civicrm_api3('CustomField', 'get', [
    'custom_group_id' => $customGroupId,
    'is_active' => 1,
  ]);

  $filterFields = [];
  foreach ($customFields['values'] as $field) {
    if (in_array($field['html_type'], ['Text', 'Select', 'Multi-Select', 'Radio'])) {
      $filterFields[] = [
        'name' => $field['name'],
        'label' => $field['label'],
        'column_name' => $field['column_name'],
      ];
    }
  }

  return [
    'customGroup' => $customGroup,
    'customFields' => $customFields['values'],
    'filterFields' => $filterFields,
    'contactId' => $contactId,
  ];
}

/**
 * Helper function to get multi-value custom groups for a contact
 */
function getMultiValueCustomGroups($contactId) {
  try {
    // Get all custom groups that are multi-value and extend contacts
    $customGroups = civicrm_api3('CustomGroup', 'get', [
      'is_multiple' => 1,
      'is_active' => 1,
      'extends' => 'Contact', // Adjust based on contact type
    ]);

    $result = [];
    foreach ($customGroups['values'] as $group) {
      // Check if this contact has any data in this custom group
      $tableName = $group['table_name'];
      $sql = "SELECT COUNT(*) FROM `{$tableName}` WHERE entity_id = %1";
      $count = CRM_Core_DAO::singleValueQuery($sql, [1 => [$contactId, 'Integer']]);

      if ($count > 0) {
        $result[$group['id']] = $group;
      }
    }

    return $result;
  }
  catch (Exception $e) {
    CRM_Core_Error::debug_log_message('Error getting multi-value custom groups: ' . $e->getMessage());
    return [];
  }
}

/**
 * Implements hook_civicrm_alterMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterMenu
 */
function customfieldfilter_civicrm_alterMenu(&$items) {
  $items['civicrm/ajax/multirecordfieldlist']['page_callback'] = 'CRM_Customfieldfilter_Utils::getMultiRecordFieldList';
}
