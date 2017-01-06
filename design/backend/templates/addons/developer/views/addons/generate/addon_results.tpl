{if $results}
    <h2>Add-on `<strong>{$results.make.addon.id}</strong>` generated</h2>
    <ul>
        {if $results.make && !empty($results.make.destinationPaths)}
            <li>files structure created: {$results.make.destinationPaths|count} files in  <code>{$results.make.workspacePath}</code></li>
        {/if}
        {if $results.archive}
            {if !empty($results.archive.path)}
                <li>archive created at: <code>{$results.archive.path}</code></li>
            {/if}
            {if !empty($results.archive.url)}
                <li>archive download URL: <a href="{$results.archive.url}">{$results.archive.url}</a></li>
            {/if}
        {/if}
    </ul>

    {include file="addons/developer/views/addons/generate/hint.tpl"}
{else}
    {__('developer.generate.error.unknown')}
{/if}