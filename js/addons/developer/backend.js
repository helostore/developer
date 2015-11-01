(function(_, $) {


    $(document).ready(function(){

        if (controller == 'addons' && mode == 'manage') {
            //$('#addons_list .btn-group.dropleft .dropdown-menu').each(function(i, ul) {
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
                    .append('<li><a class="cm-confirm" href="' + refreshTranslationUrl + '">Refresh Translations</a></li>')
                    .append('<li><a class="cm-confirm" href="' + packUrl + '">Pack</a></li>');
            });
        }
    });


}(Tygh, Tygh.$));
