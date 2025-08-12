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
 * Hook implementation to modify contact summary page
 */
function customfieldfilter_civicrm_pageRun(&$page) {
  $pageName = $page->getVar('_name');
  if ($pageName == 'CRM_Contact_Page_View_CustomData') {
    $groupId = $page->getVar('_groupId');
    // Add contact ID and group info to the page for JavaScript access
    $contactId = $page->getVar('_contactId');
    $page->assign('collapsibleFilters', FALSE);
    $page->assign('customFieldFilters_' . $groupId, FALSE);
    if ($contactId && $groupId) {
      $enabledGroups = CRM_Customfieldfilter_Utils::getEnabledGroups();
      if (in_array($groupId, $enabledGroups)) {
        $settings = Civi::settings()->get('customfieldfilter_settings') ?: [];
        if ($settings['collapsible_filters']) {
          $page->assign('collapsibleFilters', TRUE);
        }
        $enabledFields = CRM_Customfieldfilter_Utils::getEnabledFieldsForGroup($groupId);
        if (!empty($enabledFields)) {
          $page->assign('customFieldFilters_' . $groupId, TRUE);
          $controller = new CRM_Core_Controller_Simple('CRM_Customfieldfilter_Form_Filter',
            ts('Custom Filter'), NULL
          );
          $controller->setEmbedded(TRUE);
          $controller->set('groupId', $groupId);
          $controller->run();
        }
      }
    }
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

/**
 * Implements hook_civicrm_tabs().
 *
 * Add filter controls to contact summary tabs
 */
function customfieldfilter_civicrm_tabset($tabsetName, &$tabs, $context) {
  if ($tabsetName !== 'civicrm/contact/view') {
    return;
  }
  // Check if any custom field filtering is enabled
  $config = CRM_Customfieldfilter_Utils::getFilterConfiguration();;
  if (empty($config)) {
    return;
  }
  foreach ($tabs as $key => &$tab) {
    // Update the position of custom data tabs if configured
    if (strpos($tab['id'], 'custom_') === 0) {
      $groupId = str_replace('custom_', '', $tab['id']);
      if (in_array($groupId, array_keys($config))) {
        if (!empty($config[$groupId]['weight'])) {
          $tab['weight'] = $config[$groupId]['weight'];
        }
      }
    }
  }
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * Add menu item for settings
 */
function customfieldfilter_civicrm_navigationMenu(&$menu) {
  _customfieldfilter_civix_insert_navigation_menu($menu, 'Administer/Customize Data and Screens', [
    'label' => E::ts('Custom Field Filter Settings'),
    'name' => 'custom_field_filter_settings',
    'url' => 'civicrm/admin/customfieldfilter?reset=1',
    'permission' => 'administer CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
    'icon' => 'crm-i fa-filter',
  ]);
  _customfieldfilter_civix_navigationMenu($menu);
}

function customfieldfilter_civicrm_customValueTableFilter($tableName, $params, &$additionalFilter) {
  if (!empty($params) && is_array($params)) {
    $additionalFilter = ' AND (1) ';
    $additionalClauses = [];
    foreach ($params as $fName => $fValue) {
      if (substr($fName, 0, 14) == 'custom_filter_' && !empty($fValue)) {
        $columnName = substr($fName, 14);
        if (is_array($fValue)) {
          // implode array with IN clause with each value escaped
          $fValue = "'" . implode("','", $fValue) . "'";
          $additionalClauses[] = "{$columnName} IN ( $fValue )";
        }
        else {
          $additionalClauses[] = "{$columnName} LIKE '%" . CRM_Utils_Type::escape($fValue, 'String') . "%'";
        }
      }
    }
    if (!empty($additionalClauses)) {
      $additionalFilter .= ' AND ' . implode(' AND ', $additionalClauses);
    }
  }
}
