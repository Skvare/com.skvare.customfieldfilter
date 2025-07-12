<?php

use CRM_Customfieldfilter_ExtensionUtil as E;

class CRM_Customfieldfilter_Utils {
  public static function getMultiRecordFieldList(): void {
    //CRM_Core_Page_AJAX::validateAjaxRequestMethod();
    //echo '<pre>'; print_r($_GET); echo '</pre>'; exit;
    $optionalParameters = [
      'filter_field' => 'String',
      'filter_value' => 'String',
    ];

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
    $obj->_DTparams['filter_field'] = $params['filter_field'];
    $obj->_DTparams['filter_value'] = $params['filter_value'];
    if (!empty($params['sortBy'])) {
      $obj->_DTparams['sort'] = $params['sortBy'];
    }

    [$fields, $attributes] = $obj->browse();
    //CRM_Core_Error::debug_var('fields', $fields);
    //CRM_Core_Error::debug_var('$attributes', $attributes);
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

}
