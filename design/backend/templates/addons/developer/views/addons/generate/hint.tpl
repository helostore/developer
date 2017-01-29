<hr>
<h4>FAQ</h4>
<ol>
    <li>
        <h5 class="subheader hand" data-toggle="collapse" data-target="#hs_developer_faq_1">
            I'm having issues downloading the generated add-on.
            <span class="exicon-collapse"></span>
        </h5>
        <div id="hs_developer_faq_1" class="collapse">
            <p>If you choose to create a ZIP archive and you cannot download it directly, try one of these solutions:</p>
            <p>- for Apache servers, go to directory <code>{$hsWorkspacePath}</code> and create a <code>.htaccess</code> file with the following rules:</p>
            <pre><code>
    Order deny,allow
    Deny from all
    &lt;Files ~ "\.(tgz|zip)$"&gt;
    order allow,deny
    allow from all
    &lt;/Files&gt;
            </code></pre>
            <p>- for Nginx servers, add this rule to your site's <code>.conf</code> file:</p>
            <pre><code>
    #   Allow downloading archives from developer's add-on workspace path
    location {$hsWorkspaceUrl} {
        deny all;
        location ~* \.(tgz|zip)$ {
            allow all;
            expires 1M;
            add_header Cache-Control public;
            add_header Access-Control-Allow-Origin *;
        }
    }
            </code></pre>
        </div>
    </li>
</ol>
