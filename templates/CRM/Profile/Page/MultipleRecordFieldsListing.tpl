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
<div class="crm-block crm-content-block crm-profile-multiple-records-listing crm-multivalue-selector-{$customGroupId}">
  {if $customFieldFilters_{$customGroupId} }
    <details class="crm-accordion-bold crm-search_filters-accordion" {if !$collapsibleFilters}open{/if}>
      <summary>
        {ts}Filter Results by Fields{/ts}
      </summary>
      <div class="crm-accordion-body">
        <form><!-- form element is here to fool the datepicker widget -->
          <table class="no-border form-layout-compressed custom-search-options" id="multivalue-filter-{$customGroupId}">
            <tbody>
            {foreach from=$fieldNames_{$customGroupId} item=fieldName}
              <tr>
                <td class="label">
                  {$form.$fieldName.label|crmUpper}
                </td>
                <td class="crm-contact-form-block-custom_group_filter_id crm-inline-edit-field">
                  {$form.$fieldName.html|crmAddClass:'crm-inline-edit-field'}
                </td>
              </tr>
            {/foreach}
            </tbody>
          </table>
        </form>
      </div>
    </details>
  {/if}
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
                  var customGroupId = {/literal}{$customGroupId}{literal};
                  $('table.crm-multifield-selector').data({
                    "ajax": {
                      "url": {/literal}'{crmURL p="civicrm/ajax/multirecordfieldlist" h=0 q="snippet=4&cid=$contactId&cgid=$customGroupId"}'{literal},
                      "data": function (d) {
                        {/literal}{if $customFieldFilters_{$customGroupId} }{literal}
                        var formData = {};
                        $.each( CRM.vars.customFieldFilter.customFields[customGroupId], function( columnName, fieldName ){
                          $element = $('#'+fieldName);
                          var name = $element.attr('name');
                          var type = $element.attr('type');
                          var tagName = $element.prop('tagName').toLowerCase();
                          var value = null;
                          // Get value based on field type
                          switch (tagName) {
                            case 'select':
                              value = $element.val();
                              break;

                            case 'textarea':
                              value = $element.val();
                              break;

                            case 'input':
                              switch (type) {
                                case 'radio':
                                  if ($element.is(':checked')) {
                                    value = $element.val();
                                  }
                                  break;

                                case 'checkbox':
                                  if ($element.is(':checked')) {
                                    value = $element.val();
                                  }
                                  break;

                                case 'text':
                                case 'email':
                                case 'password':
                                case 'number':
                                case 'tel':
                                case 'url':
                                case 'date':
                                case 'hidden':
                                default:
                                  value = $element.val();
                                  break;
                              }
                              break;
                          }
                          // Store the value if it's not null
                          if (value !== null) {
                            if (formData[fieldName]) {
                              // Handle multiple values for same name (checkboxes, radio groups)
                              if (!$.isArray(formData[fieldName])) {
                                formData[fieldName] = [formData[fieldName]];
                              }
                              formData[fieldName].push(value);
                            } else {
                              formData[fieldName] = value;
                            }
                          }
                        });
                        $.each( formData, function( key, value ){
                          if (jQuery.isArray(value)) {
                            //value = value.join(',');
                          }
                         d[key] = value;
                        });
                        {/literal}{/if}{literal}
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
                  {/literal}{if $customFieldFilters_{$customGroupId} }{literal}
                  $('#multivalue-filter-' + customGroupId +' :input').change(function(){
                    $('table.crm-multifield-selector').DataTable().draw();
                  });
                  {/literal}{/if}{literal}
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

