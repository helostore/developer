{if !$runtime.company_id && !"RESTRICTED_ADMIN"|defined}

    {capture name="generate_addon"}
        {include file="addons/developer/views/addons/generate/addon.tpl"}
    {/capture}

    {include
        file="common/popupbox.tpl"
        id="generate_addon"
        text=__('developer.generate.addon')
        title=__('developer.generate.addon')
        content=$smarty.capture.generate_addon
        href="addons.generate"|fn_url
        act="general"
        link_class=""
        icon="icon-fire"
        link_text=__('developer.generate')}
{/if}
