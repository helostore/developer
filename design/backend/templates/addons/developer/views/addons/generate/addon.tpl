<div id="generate_addon_container" class="generate-addon">
    <form action="{""|fn_url}" method="post" name="addon_generate_form" class="form-edit form-horizontal cm-ajax_____" enctype="multipart/form-data">
        <input type="hidden" name="result_ids" value="generate_addon_container" />
        <div class="generate-addon-wrapper">

            {$_addon = "developer"}
            {foreach from=$hsAddonFields key="section" item="field_item"}

                {if $subsections.$section.type == "SEPARATE_TAB"}
                    {capture name="separate_section"}
                {/if}

                <div id="content_{$_addon}_{$section}" class="settings{if $subsections.$section.type == "SEPARATE_TAB"} cm-hide-save-button{/if}">
                    {capture name="header_first"}false{/capture}

                    {foreach from=$field_item key="name" item="data" name="fe_addons"}

                        {if $data.parent_id && $field_item[$data.parent_id]}
                            {$parent_item = $field_item[$data.parent_id]}
                            {$parent_item_html_id = "addon_option_`$_addon`_`$parent_item.name`"}
                        {else}
                            {$parent_item = []}
                            {$parent_item_html_id = ""}
                        {/if}

                        {include file="common/settings_fields.tpl"
                            item=$data
                            section=$_addon
                            html_id="addon_option_`$_addon`_`$data.name`"
                            html_name="addon_data[options][`$data.name`]"
                            index=$smarty.foreach.fe_addons.iteration
                            total=$smarty.foreach.fe_addons.total
                            class="setting-wide"
                            parent_item=$parent_item
                            parent_item_html_id=$parent_item_html_id}
                    {/foreach}
                </div>

                {if $subsections.$section.type == "SEPARATE_TAB"}
                {/capture}
                    {assign var="sep_sections" value="`$sep_sections` `$smarty.capture.separate_section`"}
                {/if}
            {/foreach}

            {include file="addons/developer/views/addons/generate/hint.tpl"}

        </div>

        <div class="buttons-container">
            {include file="buttons/save_cancel.tpl" but_name="dispatch[addons.generate]" cancel_action="close" but_text=__("generate")}

        </div>
    </form>
<!--generate_addon_container--></div>
