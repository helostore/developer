{if !$runtime.company_id && !"RESTRICTED_ADMIN"|defined}
    {capture name="mainbox"}
        {include file="addons/developer/views/addons/generate/addon.tpl"}
    {/capture}
    {include file="common/mainbox.tpl" title=__('developer.generate.addon') content=$smarty.capture.mainbox}
{/if}