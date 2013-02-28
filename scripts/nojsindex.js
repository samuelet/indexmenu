/**
 * Javascript for index view
 *
 * @author Gerrit Uitslag <klapinklapin@gmail.com>
 */

jQuery(function () {

    jQuery('.indexmenu_nojs').each(function () {
        var $tree = jQuery(this);
        var jsajax = $tree.data('jsajax');

        $tree.dw_tree({
            toggle_selector: 'a.indexmenu_idx',
            load_data: function (show_sublist, $clicky) {

                jQuery.post(
                    DOKU_BASE + 'lib/exe/ajax.php',
                    'call=indexmenu&req=index&nojs=1&' + $clicky[0].search.substr(1) + '&max=1' + decodeURIComponent(jsajax),
                    show_sublist,
                    'html'
                );
            }
        });
    });

});