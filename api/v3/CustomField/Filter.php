<?php
/**
 * CustomField.Filter API
 *
 * Provides filtering functionality for multi-value custom fields
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 */
function civicrm_api3_custom_field_filter($params) {
  try {
    // Validate required parameters
    $contactId = CRM_Utils_Array::value('contact_id', $params);
    $customGroupId = CRM_Utils_Array::value('custom_group_id', $params);

    if (empty($contactId)) {
      return civicrm_api3_create_error('contact_id is required');
    }

    if (empty($customGroupId)) {
      return civicrm_api3_create_error('custom_group_id is required');
    }

    // Validate contact exists
    if (!CRM_Contact_BAO_Contact::checkContactId($contactId)) {
      return civicrm_api3_create_error('Invalid contact_id');
    }

    // Get custom group information
    try {
      $customGroup = civicrm_api3('CustomGroup', 'getsingle', [
        'id' => $customGroupId,
        'is_active' => 1,
      ]);
    }
    catch (Exception $e) {
      return civicrm_api3_create_error('Invalid custom_group_id or group is not active');
    }

    // Verify this is a multi-value group
    if (empty($customGroup['is_multiple'])) {
      return civicrm_api3_create_error('Custom group must be multi-value');
    }

    $tableName = $customGroup['table_name'];
    $filterField = CRM_Utils_Array::value('filter_field', $params, '');
    $filterValue = CRM_Utils_Array::value('filter_value', $params, '');
    $sortField = CRM_Utils_Array::value('sort_field', $params, 'id');
    $sortOrder = CRM_Utils_Array::value('sort_order', $params, 'ASC');
    $limit = CRM_Utils_Array::value('limit', $params, 100);
    $offset = CRM_Utils_Array::value('offset', $params, 0);

    // Validate sort order
    $sortOrder = strtoupper($sortOrder);
    if (!in_array($sortOrder, ['ASC', 'DESC'])) {
      $sortOrder = 'ASC';
    }

    // Build base query
    $sql = "SELECT * FROM `{$tableName}` WHERE entity_id = %1";
    $sqlParams = [
      1 => [$contactId, 'Integer']
    ];
    $paramCount = 1;

    // Add filter conditions
    if (!empty($filterField) && !empty($filterValue)) {
      // Validate filter field exists in table
      $validFields = getCustomTableFields($tableName);
      if (!in_array($filterField, $validFields)) {
        return civicrm_api3_create_error('Invalid filter_field: ' . $filterField);
      }

      // Determine filter type based on field
      $fieldInfo = getCustomFieldInfo($customGroupId, $filterField);

      if ($fieldInfo && in_array($fieldInfo['data_type'], ['Integer', 'Float', 'Money'])) {
        // Numeric field - exact match or range
        if (strpos($filterValue, '-') !== FALSE) {
          // Range filter (e.g., "100-500")
          $range = explode('-', $filterValue, 2);
          if (count($range) == 2 && is_numeric($range[0]) && is_numeric($range[1])) {
            $sql .= " AND `{$filterField}` BETWEEN %{paramCount} AND %{paramCount2}";
            $sqlParams[++$paramCount] = [floatval($range[0]), 'Float'];
            $sqlParams[++$paramCount] = [floatval($range[1]), 'Float'];
          }
        }
        elseif (is_numeric($filterValue)) {
          // Exact numeric match
          $sql .= " AND `{$filterField}` = %{paramCount}";
          $sqlParams[++$paramCount] = [floatval($filterValue), 'Float'];
        }
      }
      elseif ($fieldInfo && $fieldInfo['data_type'] == 'Date') {
        // Date field - support various date formats
        $dateValue = date('Y-m-d', strtotime($filterValue));
        if ($dateValue && $dateValue != '1970-01-01') {
          $sql .= " AND DATE(`{$filterField}`) = %{paramCount}";
          $sqlParams[++$paramCount] = [$dateValue, 'String'];
        }
      }
      else {
        // Text field - partial match
        $sql .= " AND `{$filterField}` LIKE %{paramCount}";
        $sqlParams[++$paramCount] = ['%' . $filterValue . '%', 'String'];
      }
    }

    // Add sorting
    if (in_array($sortField, getCustomTableFields($tableName))) {
      $sql .= " ORDER BY `{$sortField}` {$sortOrder}";
    }
    else {
      $sql .= " ORDER BY id {$sortOrder}";
    }

    // Add pagination
    $sql .= " LIMIT %{limitParam} OFFSET %{offsetParam}";
    $sqlParams[++$paramCount] = [$limit, 'Integer'];
    $sqlParams[++$paramCount] = [$offset, 'Integer'];

    // Update parameter placeholders
    $finalSql = $sql;
    $finalParams = [];
    $placeholder = 2;
    foreach ($sqlParams as $key => $value) {
      if ($key == 1) {
        continue;
      } // Skip the first one
      $finalSql = str_replace("%{$key}", "%{$placeholder}", $finalSql);
      $finalParams[$placeholder] = $value;
      $placeholder++;
    }
    $finalSql = str_replace('%limitParam', "%{$placeholder}", $finalSql);
    $finalParams[$placeholder] = [$limit, 'Integer'];
    $placeholder++;
    $finalSql = str_replace('%offsetParam', "%{$placeholder}", $finalSql);
    $finalParams[$placeholder] = [$offset, 'Integer'];

    // Add contact_id back
    $finalParams[1] = [$contactId, 'Integer'];

    // Execute query
    $dao = CRM_Core_DAO::executeQuery($finalSql, $finalParams);

    $results = [];
    $customFields = getCustomFieldsForGroup($customGroupId);

    while ($dao->fetch()) {
      $row = [];
      foreach (get_object_vars($dao) as $key => $value) {
        if (strpos($key, '_') !== 0 && $key !== 'N') {
          // Format field values based on type
          if (isset($customFields[$key])) {
            $value = formatCustomFieldValue($value, $customFields[$key]);
          }
          $row[$key] = $value;
        }
      }
      $results[] = $row;
    }

    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total FROM `{$tableName}` WHERE entity_id = %1";
    if (!empty($filterField) && !empty($filterValue)) {
      // Add the same filter conditions for count
      $countSql .= " AND `{$filterField}` LIKE %2";
      $countParams = [
        1 => [$contactId, 'Integer'],
        2 => ['%' . $filterValue . '%', 'String']
      ];
    }
    else {
      $countParams = [1 => [$contactId, 'Integer']];
    }

    $totalCount = CRM_Core_DAO::singleValueQuery($countSql, $countParams);

    return civicrm_api3_create_success($results, $params, 'CustomField', 'filter', NULL, [
      'total_count' => $totalCount,
      'filtered_count' => count($results),
      'offset' => $offset,
      'limit' => $limit
    ]);

  }
  catch (Exception $e) {
    return civicrm_api3_create_error('Filter operation failed: ' . $e->getMessage());
  }
}

/**
 * CustomField.Filter API specification
 */
function _civicrm_api3_custom_field_filter_spec(&$spec) {
  $spec['contact_id'] = [
    'name' => 'contact_id',
    'title' => 'Contact ID',
    'description' => 'The contact ID to filter custom fields for',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  ];

  $spec['custom_group_id'] = [
    'name' => 'custom_group_id',
    'title' => 'Custom Group ID',
    'description' => 'The custom group ID to filter',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  ];

  $spec['filter_field'] = [
    'name' => 'filter_field',
    'title' => 'Filter Field',
    'description' => 'The custom field column name to filter by',
    'type' => CRM_Utils_Type::T_STRING,
  ];

  $spec['filter_value'] = [
    'name' => 'filter_value',
    'title' => 'Filter Value',
    'description' => 'The value to filter by',
    'type' => CRM_Utils_Type::T_STRING,
  ];

  $spec['sort_field'] = [
    'name' => 'sort_field',
    'title' => 'Sort Field',
    'description' => 'Field to sort by',
    'type' => CRM_Utils_Type::T_STRING,
    'api.default' => 'id',
  ];

  $spec['sort_order'] = [
    'name' => 'sort_order',
    'title' => 'Sort Order',
    'description' => 'Sort order (ASC or DESC)',
    'type' => CRM_Utils_Type::T_STRING,
    'api.default' => 'ASC',
  ];

  $spec['limit'] = [
    'name' => 'limit',
    'title' => 'Limit',
    'description' => 'Maximum number of results',
    'type' => CRM_Utils_Type::T_INT,
    'api.default' => 100,
  ];

  $spec['offset'] = [
    'name' => 'offset',
    'title' => 'Offset',
    'description' => 'Number of results to skip',
    'type' => CRM_Utils_Type::T_INT,
    'api.default' => 0,
  ];
}

/**
 * Helper function to get custom table fields
 */
function getCustomTableFields($tableName) {
  static $cache = [];

  if (!isset($cache[$tableName])) {
    $sql = "SHOW COLUMNS FROM `{$tableName}`";
    $dao = CRM_Core_DAO::executeQuery($sql);

    $fields = [];
    while ($dao->fetch()) {
      $fields[] = $dao->Field;
    }
    $cache[$tableName] = $fields;
  }

  return $cache[$tableName];
}

/**
 * Helper function to get custom field info
 */
function getCustomFieldInfo($customGroupId, $columnName) {
  try {
    $result = civicrm_api3('CustomField', 'getsingle', [
      'custom_group_id' => $customGroupId,
      'column_name' => $columnName,
    ]);
    return $result;
  }
  catch (Exception $e) {
    return NULL;
  }
}

/**
 * Helper function to get all custom fields for a group
 */
function getCustomFieldsForGroup($customGroupId) {
  static $cache = [];

  if (!isset($cache[$customGroupId])) {
    try {
      $result = civicrm_api3('CustomField', 'get', [
        'custom_group_id' => $customGroupId,
        'is_active' => 1,
      ]);

      $fields = [];
      foreach ($result['values'] as $field) {
        $fields[$field['column_name']] = $field;
      }
      $cache[$customGroupId] = $fields;
    }
    catch (Exception $e) {
      $cache[$customGroupId] = [];
    }
  }

  return $cache[$customGroupId];
}

/**
 * Helper function to format custom field values
 */
function formatCustomFieldValue($value, $fieldInfo) {
  if (empty($value)) {
    return $value;
  }

  switch ($fieldInfo['data_type']) {
    case 'Date':
      return date('Y-m-d', strtotime($value));

    case 'Money':
      return number_format((float)$value, 2);

    case 'Boolean':
      return $value ? 'Yes' : 'No';

    default:
      return $value;
  }
}
