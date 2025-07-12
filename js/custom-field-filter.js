/**
 * Custom Field Filter JavaScript functionality
 * Handles AJAX filtering for multi-value custom fields
 */

CRM.$(function($) {
  'use strict';

  // Global object to store filter instances
  window.CustomFieldFilter = window.CustomFieldFilter || {};

  /**
   * Initialize custom field filter for a specific group
   * @param {number} groupId - Custom group ID
   * @param {number} contactId - Contact ID
   * @param {Object} options - Configuration options
   */
  window.CustomFieldFilter.init = function(groupId, contactId, options) {
    var defaults = {
      tableSelector: '#custom-table-' + groupId,
      containerSelector: '#custom-field-tab-' + groupId,
      loadingSelector: '.loading-indicator',
      filterFieldSelector: '#filter-field-' + groupId,
      filterValueSelector: '#filter-value-' + groupId,
      resultsSummarySelector: '.results-summary',
      debounceTime: 300
    };

    var config = $.extend({}, defaults, options);
    var $container = $(config.containerSelector);
    var $table = $(config.tableSelector);
    var $tbody = $table.find('tbody');
    var $loadingIndicator = $container.find(config.loadingSelector);
    var $resultsummary = $container.find(config.resultsSummarySelector);

    // Store original data for reset functionality
    var originalData = [];
    var currentData = [];
    var filterTimeout;

    /**
     * Apply filter with current field and value selections
     */
    function applyFilter() {
      var filterField = $(config.filterFieldSelector).val();
      var filterValue = $(config.filterValueSelector).val();

      // Show loading state
      showLoading(true);

      // Clear any existing timeout
      if (filterTimeout) {
        //clearTimeout(filterTimeout);
      }

      // Debounce the filter request
      filterTimeout = setTimeout(function() {
        var apiParams = {
          contact_id: contactId,
          custom_group_id: groupId
        };

        if (filterField && filterValue) {
          apiParams.filter_field = filterField;
          apiParams.filter_value = filterValue;
        }
          /*
        // Make API call
        CRM.api3('CustomField', 'filter', apiParams)
          .done(function(result) {
            currentData = result.values || [];
            populateTable(currentData);
            updateResultsSummary(currentData.length, originalData.length, filterValue);
            showLoading(false);
          })
          .fail(function(xhr, status, error) {
            console.error('Filter API error:', error);
            showError('Error applying filter: ' + (error.message || error));
            showLoading(false);
          });
        */
      }, config.debounceTime);
    }

    /**
     * Populate table with filtered data
     * @param {Array} data - Array of record objects
     */
    function populateTable(data) {
      $tbody.empty();

      if (data.length === 0) {
        var colspan = $table.find('thead th').length;
        $tbody.append(
          '<tr><td colspan="' + colspan + '" class="text-center empty-message">' +
          '<div class="empty-state">' +
          '<i class="crm-i fa-search" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i>' +
          '<p>No records found matching your filter criteria.</p>' +
          '</div></td></tr>'
        );
        return;
      }

      // Get field mappings from table headers
      var fieldMappings = getFieldMappings();

      data.forEach(function(row, index) {
        var $row = $('<tr>').addClass('data-row');

        // Add zebra striping
        if (index % 2 === 1) {
          $row.addClass('even');
        }

        // Add data cells based on field mappings
        fieldMappings.forEach(function(field) {
          var cellValue = row[field] || '';

          // Format certain field types
          if (field.includes('date') && cellValue) {
            cellValue = formatDate(cellValue);
          } else if (field.includes('currency') && cellValue) {
            cellValue = formatCurrency(cellValue);
          }

          $row.append('<td class="data-cell">' + escapeHtml(cellValue) + '</td>');
        });

        // Add action cell
        var actionHtml = buildActionButtons(row.id, row);
        $row.append('<td class="action-cell">' + actionHtml + '</td>');

        $tbody.append($row);
      });

      // Add row hover animations
      $tbody.find('tr.data-row').hover(
        function() { $(this).addClass('hover'); },
        function() { $(this).removeClass('hover'); }
      );
    }

    /**
     * Get field mappings from table headers
     * @returns {Array} Array of field names
     */
    function getFieldMappings() {
      var fields = [];
      $table.find('thead th').each(function(index) {
        var $th = $(this);
        var fieldName = $th.data('field') || $th.text().toLowerCase().replace(/\s+/g, '_');

        // Skip the actions column
        if (!$th.hasClass('actions-header') && fieldName !== 'actions') {
          fields.push(fieldName);
        }
      });
      return fields;
    }

    /**
     * Build action buttons for a record
     * @param {number} recordId - Record ID
     * @param {Object} record - Record data
     * @returns {string} HTML for action buttons
     */
    function buildActionButtons(recordId, record) {
      var editUrl = CRM.url('civicrm/contact/view/cd/edit', {
        reset: 1,
        type: 'Individual',
        cid: contactId,
        gid: groupId,
        id: recordId,
        action: 'update'
      });

      return '<div class="action-buttons">' +
        '<a href="' + editUrl + '" class="btn-edit" title="Edit Record">' +
        '<i class="crm-i fa-pencil"></i> Edit</a>' +
        '<a href="#" class="btn-delete" data-id="' + recordId + '" title="Delete Record">' +
        '<i class="crm-i fa-trash"></i> Delete</a>' +
        '<a href="#" class="btn-view" data-id="' + recordId + '" title="View Details">' +
        '<i class="crm-i fa-eye"></i> View</a>' +
        '</div>';
    }

    /**
     * Update results summary
     * @param {number} filtered - Number of filtered results
     * @param {number} total - Total number of records
     * @param {string} filterValue - Current filter value
     */
    function updateResultsSummary(filtered, total, filterValue) {
      var summaryText;

      if (filterValue) {
        summaryText = 'Showing <strong>' + filtered + ' of ' + total + '</strong> records matching "' +
          escapeHtml(filterValue) + '"';
      } else {
        summaryText = 'Showing <strong>' + filtered + ' of ' + total + '</strong> records';
      }

      $resultsummary.find('.summary-text').html(summaryText);

      // Update summary styling based on results
      if (filtered === 0 && filterValue) {
        $resultsummary.removeClass('info').addClass('warning');
      } else {
        $resultsummary.removeClass('warning').addClass('info');
      }
    }

    /**
     * Show/hide loading indicator
     * @param {boolean} show - Whether to show loading
     */
    function showLoading(show) {
      if (show) {
        $loadingIndicator.fadeIn(200);
        $table.addClass('loading');
      } else {
        $loadingIndicator.fadeOut(200);
        $table.removeClass('loading');
      }
    }

    /**
     * Show error message
     * @param {string} message - Error message
     */
    function showError(message) {
      CRM.alert(message, 'Filter Error', 'error', {expires: 5000});
    }

    /**
     * Clear all filters and reset to original data
     */
    function clearFilters() {
      $(config.filterFieldSelector).val('').trigger('change');
      $(config.filterValueSelector).val('');
      applyFilter(); // This will load all data since no filters are set
    }

    /**
     * Format date for display
     * @param {string} dateStr - Date string
     * @returns {string} Formatted date
     */
    function formatDate(dateStr) {
      try {
        var date = new Date(dateStr);
        return date.toLocaleDateString();
      } catch (e) {
        return dateStr;
      }
    }

    /**
     * Format currency for display
     * @param {string|number} amount - Currency amount
     * @returns {string} Formatted currency
     */
    function formatCurrency(amount) {
      try {
        return '$' + parseFloat(amount).toFixed(2);
      } catch (e) {
        return amount;
      }
    }

    /**
     * Escape HTML to prevent XSS
     * @param {string} str - String to escape
     * @returns {string} Escaped string
     */
    function escapeHtml(str) {
      var div = document.createElement('div');
      div.textContent = str;
      return div.innerHTML;
    }

    // Event handlers
    $container.on('click', '.apply-filter', function(e) {
      e.preventDefault();
      applyFilter();
    });

    $container.on('click', '.clear-filter', function(e) {
      e.preventDefault();
      clearFilters();
    });

    // Real-time filtering on input
    $(config.filterValueSelector).on('input', function() {
      if ($(config.filterFieldSelector).val()) {
        applyFilter();
      }
    });

    // Apply filter when field selection changes
    $(config.filterFieldSelector).on('change', function() {
      if ($(this).val() && $(config.filterValueSelector).val()) {
        applyFilter();
      }
    });

    // Enter key support
    $(config.filterValueSelector).on('keypress', function(e) {
      if (e.which === 13) {
        e.preventDefault();
        applyFilter();
      }
    });

    // Delete record handler
    $container.on('click', '.btn-delete', function(e) {
      e.preventDefault();
      var recordId = $(this).data('id');

      CRM.confirm({
        title: 'Delete Record',
        message: 'Are you sure you want to delete this record? This action cannot be undone.'
      }).on('crmConfirm:yes', function() {
        deleteRecord(recordId);
      });
    });

    // View record handler
    $container.on('click', '.btn-view', function(e) {
      e.preventDefault();
      var recordId = $(this).data('id');
      viewRecord(recordId);
    });

    /**
     * Delete a record
     * @param {number} recordId - Record ID to delete
     */
    function deleteRecord(recordId) {
      showLoading(true);

      CRM.api3('CustomValue', 'delete', {
        id: recordId,
        entity_id: contactId
      }).done(function(result) {
        CRM.alert('Record deleted successfully', 'Success', 'success');
        applyFilter(); // Refresh the table
      }).fail(function(xhr, status, error) {
        showError('Error deleting record: ' + (error.message || error));
        showLoading(false);
      });
    }

    /**
     * View record details
     * @param {number} recordId - Record ID to view
     */
    function viewRecord(recordId) {
      // Find the record data
      var record = currentData.find(function(r) { return r.id == recordId; });

      if (record) {
        var content = '<div class="record-details">';
        Object.keys(record).forEach(function(key) {
          if (key !== 'id' && key !== 'entity_id') {
            content += '<div class="detail-row">' +
              '<strong>' + key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) + ':</strong> ' +
              '<span>' + escapeHtml(record[key] || '') + '</span>' +
              '</div>';
          }
        });
        content += '</div>';

        CRM.alert(content, 'Record Details', 'info', {expires: 0});
      }
    }

    // Initialize with all data
   // applyFilter();

    // Store the filter instance for external access
    window.CustomFieldFilter.instances = window.CustomFieldFilter.instances || {};
    window.CustomFieldFilter.instances[groupId] = {
      applyFilter: applyFilter,
      clearFilters: clearFilters,
      groupId: groupId,
      contactId: contactId
    };
  };

  // Auto-initialize filters when DOM is ready
  $(document).ready(function() {
    $('.custom-field-container[data-group-id]').each(function() {
      var $container = $(this);
      var groupId = $container.data('group-id');
      var contactId = $container.data('contact-id');

      if (groupId && contactId) {
        window.CustomFieldFilter.init(groupId, contactId);
      }
    });
  });
});
