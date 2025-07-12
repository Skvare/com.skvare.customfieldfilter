<?php

class CRM_Customfieldfilter_Form_Filter extends CRM_Core_Form {

  public $_groupId = '';

  public function buildQuickForm() {
    $this->_groupId = CRM_Utils_Request::retrieve('groupId', 'Positive', $this);
    if (empty($this->_groupId)) {
      return;
    }
    $enabledFields =
      CRM_Customfieldfilter_Utils::getEnabledFieldsForGroup($this->_groupId);
    if (!empty($enabledFields)) {
      $customFields = civicrm_api3('CustomField', 'get', [
        'custom_group_id' => $this->_groupId,
        'id' => ['IN' => $enabledFields],
        'is_active' => 1,
      ]);
      $fieldNames = [];
      foreach ($customFields['values'] as $customField) {
        CRM_Core_BAO_CustomField::addQuickFormElement($this, 'custom_filter_' . $customField['column_name'], $customField['id'], FALSE, TRUE);
        $fieldNames[$customField['column_name']] = 'custom_filter_' . $customField['column_name'];
      }
      $this->assign('fieldNames_' . $this->_groupId, $fieldNames);

      CRM_Core_Resources::singleton()->addVars('customFieldFilter', [
        'customGroupId' => $this->_groupId,
        'customFields' => [$this->_groupId => $fieldNames],
      ]);
    }
  }
}
