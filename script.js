
// Context menu
var indexmenu_contextmenu = {'all': []};

/* DOKUWIKI:include scripts/nojsindex.js */
/* DOKUWIKI:include scripts/toolbarindexwizard.js */
/* DOKUWIKI:include scripts/contextmenu.js */
/* DOKUWIKI:include scripts/indexmenu.js */
/* DOKUWIKI:include scripts/contextmenu.local.js */


/**
 * Add button action for the indexmenu wizard button
 *
 * @param  {jQuery}   $btn  Button element to add the action to
 * @param  {Array}    props Associative array of button properties
 * @param  {string}   edid  ID of the editor textarea
 * @return {boolean}  If button should be appended
 */
function addBtnActionIndexmenu($btn, props, edid) {
    indexmenu_wiz.init(jQuery('#' + edid));
    $btn.click(function () {
        indexmenu_wiz.toggle();
        return false;
    });
    return true;
}


// try to add button to toolbar
if (window.toolbar != undefined) {
    window.toolbar[window.toolbar.length] = {
        "type": "Indexmenu",
        "title": "Insert the Indexmenu tree",
        "icon": "../../plugins/indexmenu/images/indexmenu_toolbar.png"
    }
}


/**
 *  functions for js index renderer and contextmenu
 */
var IndexmenuUtils = {

    /**
     * Determine extension from given theme dir name
     *
     * @param {string} themedir name of theme dir
     * @returns {string} extension gif, png or jpg
     */
    determineExtension: function (themedir) {
        var extension = "gif";
        var posext = themedir.lastIndexOf(".");
        if (posext > -1) {
            posext++;
            var ext = themedir.substring(posext, themedir.length).toLowerCase();
            if ((ext == "png") || (ext == "jpg")) {
                extension = ext;
            }
        }
        return extension;
    },

    /**
     * Create div with given id and class on body and return it
     *
     * @param {string} id picker id
     * @param {string} cl class(es)
     * @return {jQuery} jQuery div
     */
    createPicker: function (id, cl) {
        return jQuery('<div>')
            .addClass(cl || 'picker')
            .attr('id', id)
            .css({position: 'absolute'})
            .hide()
            .appendTo('body');
    }

};
