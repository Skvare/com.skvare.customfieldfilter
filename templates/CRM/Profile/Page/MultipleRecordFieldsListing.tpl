{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

{* Include CiviCRM's default styling and scripts *}
{include file="CRM/common/WizardHeader.tpl"}
{assign var=filterFields2 value='filterFields_'|cat:$customGroupId}
<div class="crm-block crm-content-block crm-profile-multiple-records-listing crm-multivalue-selector-{$customGroupId}">
  {if $customFieldFilters}
    {* Enhanced Filter Section for Multi-Value Records *}
    <div class="custom-field-filters-wrapper">
      <div class="custom-field-filters" id="multivalue-filter-{$customGroupId}">
        <div class="filter-header">
          <h3 class="filter-title">
            <i class="crm-i fa-filter"></i>
            Filter {$customGroup_{$customGroupId}.title} Records
          </h3>
          <div class="filter-toggle">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="toggle-filters-{$customGroupId}">
              <i class="crm-i fa-chevron-down"></i> Show Filters
            </button>
          </div>
        </div>

        <div class="filter-content" id="filter-content-{$customGroupId}" style="display: none;">
          <div class="filter-form">
            <div class="filter-row">
              <div class="filter-group">
                <label for="filter-field-{$customGroupId}" class="filter-label">Filter by Field:</label>
                <select id="filter-field-{$customGroupId}" class="filter-field-select crm-select2 crm-inline-edit-field">
                  <option value="">-- All Fields --</option>
                  {foreach from=$filterFields_{$customGroupId} item=field}
                    <option value="{$field.column_name}" data-field-type="{$field.data_type}">{$field.label}</option>
                  {/foreach}
                </select>
              </div>

              <div class="filter-group">
                <label for="filter-value-{$customGroupId}" class="filter-label">Filter Value:</label>
                <input type="text"
                       id="filter-value-{$customGroupId}"
                       class="filter-value-input crm-form-text crm-inline-edit-field"
                       placeholder="Enter value to search for...">
              </div>

              <div class="filter-actions">
                <button type="button" class="btn btn-primary apply-filter" data-group-id="{$customGroupId}">
                  <i class="crm-i fa-search"></i> Apply Filter
                </button>
                <button type="button" class="btn btn-secondary clear-filter" data-group-id="{$customGroupId}">
                  <i class="crm-i fa-times"></i> Clear
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  {/if}


  {* Loading Indicator *}
  <div class="loading-indicator" id="loading-{$customGroupId}" style="display: none;">
    <div class="loading-content">
      <i class="crm-i fa-spinner fa-spin"></i>
      <span>Filtering records...</span>
    </div>
  </div>

{if $showListing}
  {if $dontShowTitle neq 1}<h1>{ts}{$customGroupTitle}{/ts}</h1>{/if}
  {if $pageViewType eq 'customDataView'}
     {assign var='dialogId' value='custom-record-dialog'}
  {else}
     {assign var='dialogId' value='profile-dialog'}
  {/if}
  {if ($records and $headers) or ($pageViewType eq 'customDataView')}
    {include file="CRM/common/jsortable.tpl"}
    <div id="custom-{$customGroupId}-table-wrapper" {if $pageViewType eq 'customDataView'}class="crm-entity" data-entity="contact" data-id="{$contactId}"{/if}>
      <div>
        {strip}
          <table id="records-{$customGroupId}" class={if $pageViewType eq 'customDataView'}"crm-multifield-selector crm-ajax-table"{else}'display'{/if}>
            <thead>
            {if $pageViewType eq 'customDataView'}
              {foreach from=$headers key=recId item=head}
                <th data-data={ts}'{$headerAttr.$recId.columnName}'{/ts}
                {if !empty($headerAttr.$recId.dataType)}cell-data-type="{$headerAttr.$recId.dataType}"{/if}
                {if !empty($headerAttr.$recId.dataEmptyOption)}cell-data-empty-option="{$headerAttr.$recId.dataEmptyOption}"{/if}>{ts}{$head}{/ts}
                </th>
              {/foreach}
              <th data-data="action" data-orderable="false">&nbsp;</th>
            </thead>
              {literal}
              <script type="text/javascript">
                (function($) {
                  var ZeroRecordText = {/literal}"{ts escape='js' 1=$customGroupTitle|smarty:nodefaults}No records of type '%1' found.{/ts}"{literal};
                  var $table = $('#records-' + {/literal}'{$customGroupId}'{literal});
                  var customGroupId = CRM.vars.customFieldFilter.customGroupId;
                  $('table.crm-multifield-selector').data({
                    "ajax": {
                      "url": {/literal}'{crmURL p="civicrm/ajax/multirecordfieldlist" h=0 q="snippet=4&cid=$contactId&cgid=$customGroupId"}'{literal},
                      "data": function (d) {
                        d.filter_field = $('.custom-field-filters select#filter-field-'+ customGroupId).val(),
                        d.filter_value = $('.custom-field-filters #filter-value-'+ customGroupId).val()
                      }
                    },
                    "language": {
                      "emptyTable": ZeroRecordText,
                    },
                    //Add class attributes to cells
                    "rowCallback": function(row, data) {
                      $('thead th', $table).each(function(index) {
                        var fName = $(this).attr('data-data');
                        var cell = $('td:eq(' + index + ')', row);
                        if (typeof data[fName] == 'object') {
                          if (typeof data[fName].data != 'undefined') {
                            $(cell).html(data[fName].data);
                          }
                          if (typeof data[fName].cellClass != 'undefined') {
                            $(cell).attr('class', data[fName].cellClass);
                          }
                        }
                      });
                    }
                  });
                  $('#multivalue-filter-23 :input').change(function(){
                    $('table.crm-multifield-selector').DataTable().draw();
                  });
                })(CRM.$);
              </script>
              {/literal}

            {else}
              {foreach from=$headers key=recId item=head}
                <th>{ts}{$head}{/ts}</th>
              {/foreach}

              {foreach from=$dateFields key=fieldId item=v}
                <th class='hiddenElement'></th>
              {/foreach}
              <th>&nbsp;</th>
              </thead>
              {foreach from=$records key=recId item=rows}
                <tr class="{cycle values="odd-row,even-row"}">
                  {foreach from=$headers key=hrecId item=head}
                    <td {if !empty($dateFieldsVals.$hrecId)}data-order="{$dateFieldsVals.$hrecId.$recId|crmDate:'%Y-%m-%d'}"{/if} {crmAttributes a=$attributes.$hrecId.$recId}>{$rows.$hrecId}</td>
                  {/foreach}
                  <td>{$rows.action}</td>
                  {foreach from=$dateFieldsVals key=fid item=rec}
                      <td class='crm-field-{$fid}_date hiddenElement'>{$rec.$recId}</td>
                  {/foreach}
                </tr>
              {/foreach}
            {/if}
          </table>
        {/strip}
      </div>
    </div>
    <div id='{$dialogId}' class="hiddenElement"></div>
  {elseif !$records}
    <div class="messages status no-popup">
      {icon icon="fa-info-circle"}{/icon}
      &nbsp;
      {ts 1=$customGroupTitle}No records of type '%1' found.{/ts}
    </div>
    <div id='{$dialogId}' class="hiddenElement"></div>
  {/if}

  {if empty($reachedMax) && !empty($editPermission)}
    <div class="action-link">
      {if $pageViewType eq 'customDataView'}
        <br/><a accesskey="N" title="{ts 1=$customGroupTitle}Add %1 Record{/ts}" href="{crmURL p='civicrm/contact/view/cd/edit' q="reset=1&type=$ctype&groupID=$customGroupId&entityID=$contactId&cgcount=$newCgCount&multiRecordDisplay=single&mode=add"}"
         class="button action-item"><span><i class="crm-i fa-plus-circle" aria-hidden="true"></i> {ts 1=$customGroupTitle}Add %1 Record{/ts}</span></a>
      {else}
        <a accesskey="N" href="{crmURL p='civicrm/profile/edit' q="reset=1&id=`$contactId`&multiRecord=add&gid=`$gid`&context=multiProfileDialog"}"
         class="button action-item"><span><i class="crm-i fa-plus-circle" aria-hidden="true"></i> {ts}Add New Record{/ts}</span></a>
      {/if}
    </div>
    <br />
  {/if}
{/if}

  {* Initialize JavaScript for this specific multi-value group *}
  <script type="text/javascript">
    {literal}
    setTimeout(function() {
    (function($) {
      // Initialize filter functionality when DOM is ready
      if (typeof window.CustomFieldFilter !== 'undefined' && CRM.vars.customFieldFilter) {
        var contactId = CRM.vars.customFieldFilter.contactId;
        var customGroupId = CRM.vars.customFieldFilter.customGroupId;
        console.log(customGroupId);
        console.log(contactId);
        // Initialize the multi-value filter
        window.CustomFieldFilter.init(customGroupId, contactId, {
          tableSelector: '#multivalue-table-' + customGroupId,
          containerSelector: '#multivalue-filter-' + customGroupId,
          recordsSelector: '#multivalue-table-' + customGroupId + ' tbody',
          summarySelector: '#results-summary-' + customGroupId,
          loadingSelector: '#loading-' + customGroupId,
          pageType: 'MultipleRecordFieldsListing',
          debounceTime: 500
        });

        // Filter toggle functionality
        $('#multivalue-filter-' + customGroupId).on('click', '#toggle-filters-' + customGroupId, function() {
          var $content = $('#filter-content-' + customGroupId);
          var $icon = $(this).find('i');
          console.log($content);
          if ($content.is(':visible')) {
            console.log('Hiding filters');
            $content.slideUp();
            $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
            $(this).html('<i class="crm-i fa-chevron-down"></i> Show Filters');
          } else {
            console.log('Showing filters');
            $content.slideDown();
            $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
            $(this).html('<i class="crm-i fa-chevron-up"></i> Hide Filters');
          }
        });
      }
    })(CRM.$);
    }, 2000);
    {/literal}
  </script>

