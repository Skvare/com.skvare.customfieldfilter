# CiviCRM Custom Field Filter Extension

## Overview

The **Custom Field Filter Extension** (`com.skvare.customfieldfilter`) is a CiviCRM extension developed by Skvare that adds powerful filtering capabilities to multi-value custom groups displayed as tabs in contact summary page. This extension enhances the user experience when working with contacts that have multiple entries in custom field groups by providing intuitive filtering options.

### Note: This extension works only with custom groups that have "Supports Multiple Records" enabled and the display style set to "Tab with table".

## Description

When working with CiviCRM contacts that have multi-value custom field sets on custom group, users often encounter situations where contacts have numerous entries in custom field groups, making it difficult to quickly locate specific information. This extension solves that problem by adding filtering functionality directly to the same tabs, allowing users to efficiently search and filter through multi-value custom field data.

## Key Features

- **Multi-Value Custom Field Filtering**: Add search and filter capabilities to multi-value custom field displays in custom group tabs
- **Enhanced User Experience**: Streamline the process of finding specific information within large custom field datasets
- **Real-time Filtering**: Filter results dynamically as users type or select filter criteria
- **Contact Summary Integration**: Seamlessly integrates with existing CiviCRM layout Custom group tab.
- **Flexible Configuration**: Administrators can configure which custom field groups have filtering enabled
- **Performance Optimized**: Efficient filtering that doesn't impact page load times

### Setting Form:

![Screenshot](/images/custom_filter_settings.png)

### Custom Group Tab with filter:

![Screenshot](/images/custom_filter_tab.png)


## Requirements

- **CiviCRM**: Version 5.81 or higher
- **PHP**: Version 8.1 or higher
- **MySQL**: Version 5.7 or higher / MariaDB 10.2 or higher
- **Web Server**: Apache or Nginx with mod_rewrite enabled

## Installation

### Method 1: Extension Manager (Recommended)

1. Navigate to **Administer > System Settings > Extensions**
2. Click **Add New**
3. Search for "Custom Field Filter" or use the key `com.skvare.customfieldfilter`
4. Click **Download** and then **Install**
5. Enable the extension

### Method 2: Manual Installation

1. Download the extension from the GitHub repository:
   ```bash
   git clone https://github.com/Skvare/com.skvare.customfieldfilter.git
   ```

2. Place the extension in your CiviCRM extensions directory:
   ```
   [civicrm.root]/extensions/com.skvare.customfieldfilter/
   ```

3. Navigate to **Administer > System Settings > Extensions**
4. Find the "Custom Field Filter" extension and click **Install**
5. Enable the extension

### Method 3: Command Line (cv tool)

If you have the `cv` command-line tool installed:

```bash
cd /path/to/civicrm
cv ext:download com.skvare.customfieldfilter
cv ext:enable com.skvare.customfieldfilter
```

## Configuration

### Initial Setup
1. **Access Extension Settings**: (`/civicrm/admin/customfieldfilter`)
  - Navigate to **Administer > System Settings > Custom Field Filter Settings**
  - Review and adjust settings as needed.

2. **Configure Custom Field Groups**:
  - Select which multi-value custom field groups should have filtering enabled
  - Configure  field you want to use as filter on each Custom Group
  - Change order/weight to show Custom Group tab near summary tab.

### Filter Types

The extension supports various filter types depending on the custom field type:

- **Text Fields**: Search by partial text matching
- **Select/Dropdown**: Filter by specific option values
- **Number Fields**: Filter by numeric ranges
- **Yes/No Fields**: Filter by boolean values

## Customization

* This extension overrides the template file `CRM/Profile/Page/MultipleRecordFieldsListing.tpl`.
* Please ensure this file is reviewed and updated whenever CiviCRM is upgraded.
* Additionally, the extension requires patching a core CiviCRM file.

### PATCH
```patch
diff --git a/CRM/Core/BAO/CustomValueTable.php b/CRM/Core/BAO/CustomValueTable.php
index 66e37d3557..112e0f6ac9 100644
--- a/CRM/Core/BAO/CustomValueTable.php
+++ b/CRM/Core/BAO/CustomValueTable.php
@@ -464,7 +464,17 @@ AND    $cond
         }
       }

-      $query = "SELECT SQL_CALC_FOUND_ROWS id, " . implode(', ', $clauses) . " FROM $tableName WHERE entity_id = $entityID {$orderBy} {$limit}";
+      $additionalFilter = '';
+      if (class_exists('\Civi\Core\Event\GenericHookEvent')) {
+        \Civi::dispatcher()->dispatch('hook_civicrm_customValueTableFilter',
+          \Civi\Core\Event\GenericHookEvent::create([
+            'tableName' => $tableName,
+            'params' => $DTparams,
+            'additionalFilter' => &$additionalFilter,
+          ])
+        );
+      }
+      $query = "SELECT SQL_CALC_FOUND_ROWS id, " . implode(', ', $clauses) . " FROM $tableName WHERE entity_id = $entityID {$additionalFilter} {$orderBy} {$limit}";
       $dao = CRM_Core_DAO::executeQuery($query);
       if (!empty($DTparams)) {
         $result['count'] = CRM_Core_DAO::singleValueQuery('SELECT FOUND_ROWS()');
```

## Support and Maintenance

### Getting Help

- **Documentation**: Visit [Skvare's website](https://skvare.com) for additional resources
- **Community Support**: Post questions in the CiviCRM Stack Exchange
- **Professional Support**: Contact Skvare directly for professional support and customization

### Reporting Issues

If you encounter bugs or have feature requests:

1. Check the [GitHub Issues](https://github.com/Skvare/com.skvare.customfieldfilter/issues) page
2. Search for existing issues before creating a new one
3. Provide detailed information including:
  - CiviCRM version
  - Extension version
  - Steps to reproduce the issue
  - Error messages or screenshots


### Development Setup

```bash
git clone https://github.com/Skvare/com.skvare.customfieldfilter.git
cd com.skvare.customfieldfilter
# Set up your development environment
```

## License

This extension is licensed under the [AGPL-3.0 License](LICENSE.txt).

## About Skvare

Skvare LLC specializes in CiviCRM development, Drupal integration, and providing technology solutions for nonprofit organizations, professional societies, membership-driven associations, and small businesses. We are committed to developing open source software that empowers our clients and the wider CiviCRM community.

**Contact Information**:
- Website: [https://skvare.com](https://skvare.com)
- Email: info@skvare.com
- GitHub: [https://github.com/Skvare](https://github.com/Skvare)

---

## Related Extensions

You might also be interested in other Skvare CiviCRM extensions:

- **Database Custom Field Check**: Prevents adding custom fields when table limits are reached
- **Image Resize**: Resize images uploaded to CiviCRM with different dimensions
- **Registration Button Label**: Customize button labels on event registration pages

For a complete list of our open source contributions, visit our [GitHub organization page](https://github.com/Skvare).
