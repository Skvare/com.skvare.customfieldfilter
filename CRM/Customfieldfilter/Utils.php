<?php

use CRM_Customfieldfilter_ExtensionUtil as E;

class CRM_Customfieldfilter_Utils {

  /**
   * Get enabled custom groups based on current settings
   */
  public static function getEnabledGroups() {
    $settings = Civi::settings()->get('customfieldfilter_settings') ?: [];
    $enabled = [];

    foreach ($settings as $key => $value) {
      if (strpos($key, 'enable_group_') === 0 && $value) {
        $groupId = str_replace('enable_group_', '', $key);
        $enabled[] = $groupId;
      }
    }

    return $enabled;
  }

  /**
   * Get enabled fields for a specific group
   *
   * @param $groupId
   * @return array|mixed
   */
  public static function getEnabledFieldsForGroup($groupId) {
    $settings = Civi::settings()->get('customfieldfilter_settings') ?: [];
    return $settings["fields_group_{$groupId}"] ?? [];
  }

  /**
   * Get field names for given field IDs
   *
   * @param array $fieldIds
   *   Array of custom field IDs.
   *
   * @return array
   *   Associative array of field names with keys as 'custom_filter_' . column_name
   */
  public static function getFieldName($fieldIds = [], $flip = FALSE) {
    $customFields = civicrm_api3('CustomField', 'get', [
      'id' => ['IN' => $fieldIds],
      'is_active' => 1,
    ]);
    $fieldNames = [];
    foreach ($customFields['values'] as $customField) {
      $fieldNames['custom_filter_' . $customField['column_name']] = $customField['column_name'];
    }
    if ($flip) {
      $fieldNames = array_flip($fieldNames);
    }
    return $fieldNames;
  }

  /**
   * Override function for ajax call to get multi record field list
   *
   * @return void
   * @throws CRM_Core_Exception
   */
  public static function getMultiRecordFieldList(): void {
    CRM_Core_Page_AJAX::validateAjaxRequestMethod();
    $cgid = CRM_Utils_Type::escape($_GET['cgid'], 'Integer');
    $optionalParameters = [];
    if (!empty($cgid)) {
      $enabledFields = self::getEnabledFieldsForGroup($cgid);
      if (!empty($enabledFields)) {
        $fieldNames = self::getFieldName($enabledFields);
        $optionalParameters = array_combine(array_keys($fieldNames), array_fill(0, count($fieldNames), 'String'));
      }
    }
    $params = CRM_Core_Page_AJAX::defaultSortAndPagerParams(0, 10);
    $params += CRM_Core_Page_AJAX::validateParams([], $optionalParameters);
    $params['cid'] = CRM_Utils_Type::escape($_GET['cid'], 'Integer');
    $params['cgid'] = CRM_Utils_Type::escape($_GET['cgid'], 'Integer');
    if (!CRM_Core_BAO_CustomGroup::checkGroupAccess($params['cgid'], CRM_Core_Permission::VIEW) ||
      !CRM_Contact_BAO_Contact_Permission::allow($params['cid'], CRM_Core_Permission::VIEW)
    ) {
      CRM_Utils_System::permissionDenied();
    }
    $contactType = CRM_Contact_BAO_Contact::getContactType($params['cid']);

    $obj = new CRM_Profile_Page_MultipleRecordFieldsListing();
    $obj->_pageViewType = 'customDataView';
    $obj->_contactId = $params['cid'];
    $obj->_customGroupId = $params['cgid'];
    $obj->_contactType = $contactType;
    $obj->_DTparams['offset'] = ($params['page'] - 1) * $params['rp'];
    $obj->_DTparams['rowCount'] = $params['rp'];
    foreach ($fieldNames as $cfFieldName => $value) {
      if (!empty($params[$cfFieldName])) {
        $obj->_DTparams[$cfFieldName] = $params[$cfFieldName];
      }
    }
    if (!empty($params['sortBy'])) {
      $obj->_DTparams['sort'] = $params['sortBy'];
    }

    [$fields, $attributes] = $obj->browse();
    // format params and add class attributes
    $fieldList = [];
    foreach ($fields as $id => $value) {
      foreach ($value as $fieldId => &$fieldName) {
        if (!empty($attributes[$fieldId][$id]['class'])) {
          $fieldName = ['data' => $fieldName, 'cellClass' => $attributes[$fieldId][$id]['class']];
        }
        if (is_numeric($fieldId)) {
          $fName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $fieldId, 'column_name');
          CRM_Utils_Array::crmReplaceKey($value, $fieldId, $fName);
        }
      }
      array_push($fieldList, $value);
    }
    $totalRecords = !empty($obj->_total) ? $obj->_total : 0;

    $multiRecordFields = [];
    $multiRecordFields['data'] = $fieldList;
    $multiRecordFields['recordsTotal'] = $totalRecords;
    $multiRecordFields['recordsFiltered'] = $totalRecords;

    CRM_Utils_JSON::output($multiRecordFields);
  }

  /**
   * Get filter configuration for enabled custom groups
   *
   * @return array
   */
  public static function getFilterConfiguration() {
    $settings = Civi::settings()->get('customfieldfilter_settings') ?: [];
    $config = [];

    // Get enabled groups
    $enabledGroups = CRM_Customfieldfilter_Utils::getEnabledGroups();

    foreach ($enabledGroups as $groupId) {
      $config[$groupId] = [
        'enabled_fields' => CRM_Customfieldfilter_Utils::getEnabledFieldsForGroup($groupId),
        'weight' => (int)($settings["weight_group_{$groupId}"] ?? $groupId),
      ];
    }

    // Sort by weight
    uasort($config, function ($a, $b) {
      return $a['weight'] <=> $b['weight'];
    });

    return $config;
  }
}
