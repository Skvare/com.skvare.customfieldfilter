{*
 * Admin Settings Template for Custom Field Filter Configuration
 *}

<div class="crm-form-block crm-block crm-customfieldfilter-settings-form-block">

  {* Page header *}
  <div class="crm-page-header">
    <h1>{ts}Custom Field Filter Settings{/ts}</h1>
    <p class="description">
      {ts}Configure which custom groups and fields should be available for filtering on contact summary tabs. Select groups, individual fields, and display options for enhanced search functionality.{/ts}
    </p>
  </div>

  {* Summary Tab Display Settings *}
  <div class="crm-accordion-wrapper">
    <div class="crm-accordion-header">
      <div class="icon crm-accordion-pointer"></div>
      {ts}Summary Tab Display Settings{/ts}
    </div>
    <div class="crm-accordion-body">
      <table class="form-layout">
        <tr class="crm-customfieldfilter-settings-form-block-collapsible_filters">
          <td class="label">{$form.collapsible_filters.label}</td>
          <td>{$form.collapsible_filters.html}
            <div class="description">{ts}Allow users to collapse/expand filter sections to save space.{/ts}</div>
          </td>
        </tr>
      </table>
    </div>
  </div>

  {* Custom Groups Configuration *}
  <div class="crm-accordion-wrapper">
    <div class="crm-accordion-header">
      <div class="icon crm-accordion-pointer"></div>
      {ts}Custom Groups & Fields Configuration{/ts}
    </div>
    <div class="crm-accordion-body">
      <div class="description" style="margin-bottom: 15px;">
        {ts}Select which custom groups and their fields should be available for filtering. Configure display options and ordering for each group.{/ts}
      </div>

      {if $customGroups}
        {foreach from=$customGroups key=groupId item=groupData}
          {assign var="enable_group_formElement" value="enable_group_"|cat:$groupId}
          {assign var="fields_group_formElement" value="fields_group_"|cat:$groupId}
          {assign var="weight_group_formElement" value="weight_group_"|cat:$groupId}

          <div class="crm-section custom-group-section" style="border: 1px solid #ccc; margin-bottom: 20px; padding: 15px; border-radius: 4px;">

            {* Group Header *}
            <div class="custom-group-header" style="background: #f5f5f5; margin: -15px -15px 15px -15px; padding: 10px 15px; border-bottom: 1px solid #ddd;">
              <h3 style="margin: 0; font-size: 16px; color: #333;">
                <i class="crm-i fa-folder-open" style="margin-right: 8px;"></i>
                {$groupData.title}
                <small style="color: #666; font-weight: normal;">
                  ({ts}Extends:{/ts} {$groupData.extends} | {ts}Fields:{/ts} {$groupData.fields|@count})
                </small>
              </h3>
            </div>

            {* Enable Group Checkbox *}
            <div class="crm-section" style="margin-bottom: 15px;">
              <div class="label" style="width: 200px; display: inline-block; vertical-align: top;">
                {$form.$enable_group_formElement.label}
              </div>
              <div class="content" style="display: inline-block;">
                {$form.$enable_group_formElement.html}
                <div class="description">{ts}Enable this entire custom group for filtering on summary tabs.{/ts}</div>
              </div>
            </div>

            {* Field Selection - Only show if group has fields *}
            {if $groupData.fields|@count > 0}
              <div class="crm-section enable-dependent" style="margin-bottom: 15px;">
                <div class="label" style="width: 200px; display: inline-block; vertical-align: top;">
                  {$form.$fields_group_formElement.label}
                </div>
                <div class="content" style="display: inline-block; width: 400px;">
                  {$form.$fields_group_formElement.html}
                  <div class="description">{ts}Select specific fields from this group to enable for filtering. Leave empty to enable all fields.{/ts}</div>
                </div>
              </div>

              {* Display Order *}
              <div class="crm-section enable-dependent" style="margin-bottom: 15px;">
                <div class="label" style="width: 200px; display: inline-block; vertical-align: top;">
                  {$form.$weight_group_formElement.label}
                </div>
                <div class="content" style="display: inline-block;">
                  {$form.$weight_group_formElement.html}
                  <div class="description">{ts}Lower numbers appear first. Use this to control the order of filter groups.{/ts}</div>
                </div>
              </div>

              {* Field List Preview *}
              <div class="crm-section" style="margin-top: 10px;">
                <div class="label" style="width: 200px; display: inline-block; vertical-align: top;">
                  <strong>{ts}Available Fields:{/ts}</strong>
                </div>
                <div class="content" style="display: inline-block;">
                  <ul style="margin: 0; padding-left: 15px; font-size: 12px; color: #666;">
                    {foreach from=$groupData.fields key=fieldId item=fieldData}
                      <li style="margin-bottom: 3px;">
                        <strong>{$fieldData.label}</strong>
                        <em>({$fieldData.data_type} - {$fieldData.html_type})</em>
                      </li>
                    {/foreach}
                  </ul>
                </div>
              </div>

            {else}
              <div class="crm-section">
                <div class="content">
                  <div class="status message" style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 8px 12px; border-radius: 4px;">
                    <i class="crm-i fa-info-circle"></i>
                    {ts}This custom group has no active fields. Add fields to this group to enable filtering options.{/ts}
                  </div>
                </div>
              </div>
            {/if}
          </div>
        {/foreach}
      {else}
        <div class="crm-section">
          <div class="content">
            <div class="status message" style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 12px; border-radius: 4px;">
              <i class="crm-i fa-exclamation-triangle"></i>
              <strong>{ts}No Custom Groups Found{/ts}</strong><br/>
              {ts}You need to create custom groups and fields before you can configure filtering. <a href="{crmURL p="civicrm/admin/custom/group" q="reset=1"}">Create Custom Groups</a>{/ts}
            </div>
          </div>
        </div>
      {/if}
    </div>
  </div>

  {* Form Buttons *}
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>

{* JavaScript for form behavior *}
{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      // Toggle dependent fields based on group enable checkbox
      $('[id^="enable_group_"]').each(function() {
        var groupId = this.id.replace('enable_group_', '');
        var $dependentFields = $(this).closest('.custom-group-section').find('.enable-dependent');

        // Function to toggle dependent fields
        function toggleFields() {
          if ($(this).is(':checked')) {
            $dependentFields.show().find('select, input').prop('disabled', false);
          } else {
            $dependentFields.hide().find('select, input').prop('disabled', true);
          }
        }

        // Initial state
        toggleFields.call(this);

        // Bind to change event
        $(this).change(toggleFields);
      });

      // Add visual feedback for enabled groups
      $('[id^="enable_group_"]').change(function() {
        var $section = $(this).closest('.custom-group-section');
        if ($(this).is(':checked')) {
          $section.addClass('enabled-group').css('border-color', '#5cb85c');
        } else {
          $section.removeClass('enabled-group').css('border-color', '#ccc');
        }
      }).trigger('change');
    });
  </script>
{/literal}

{* CSS for better styling *}
{literal}
  <style type="text/css">
    .custom-group-section.enabled-group {
      box-shadow: 0 2px 4px rgba(92, 184, 92, 0.1);
    }

    .custom-group-section .enable-dependent {
      padding-left: 20px;
      border-left: 3px solid #f0f0f0;
      margin-left: 10px;
    }

    .custom-group-section.enabled-group .enable-dependent {
      border-left-color: #5cb85c;
    }

    .crm-accordion-wrapper .crm-accordion-body {
      padding: 15px;
    }

    .form-layout td.label {
      vertical-align: top;
      padding-top: 8px;
    }

    .description {
      font-size: 11px;
      color: #666;
      margin-top: 4px;
      line-height: 1.3;
    }

    .custom-group-header i {
      color: #337ab7;
    }
  </style>
{/literal}
