(function(_, $) {

    $(document).ready(function(){

        if (!_ || !_.developer) {
            return;
        }

        var controller = _.developer.runtime.controller;
        var mode = _.developer.runtime.mode;

        if (controller && mode) {
            var $body = $('body');
            $body.addClass('hs-ctrl-' + controller)
                .addClass('hs-mode-' + mode)
                .addClass('hs-' + controller + '-' + mode);
            if (_.developer.platform.versionParsed) {
                $body.addClass('hs-csc-' +  _.developer.platform.versionParsed.major)
                    .addClass('hs-csc-' +  _.developer.platform.versionParsed.major + '-' + _.developer.platform.versionParsed.minor)
                    .addClass('hs-csc-' +  _.developer.platform.versionParsed.major + '-' + _.developer.platform.versionParsed.minor + '-' + _.developer.platform.versionParsed.patch);
            }

            if (controller == 'addons' && mode == 'manage') {
                $('#addons_list .cm-row-item').each(function(i, tr) {

                    var $tr = $(tr)
                        , id = $tr.attr('id')
                        , addonName = id.replace(/^addon_/, '').replace(/installed/, '')
                        , $ul = $tr.find('.btn-group.dropleft .dropdown-menu')
                        , reinstallUrl = fn_url('addons.reinstall?addon=' + addonName)
                        , packUrl = fn_url('addons.pack?addon=' + addonName)
                        , refreshTranslationUrl = fn_url('addons.refresh_translations?addon=' + addonName)
                        ;
                    $ul.append('<li class="divider"></li>')
                        .append('<li><a class="cm-confirm" href="' + reinstallUrl + '">Reinstall</a></li>')
                        .append('<li><a class="" href="' + refreshTranslationUrl + '">Refresh Translations</a></li>')
                        .append('<li><a class="cm-confirm" href="' + packUrl + '">Pack</a></li>');
                });
            }
        }

    });

}(Tygh, Tygh.$));
