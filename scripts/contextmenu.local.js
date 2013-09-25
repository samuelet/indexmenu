/**
 * Right Context Menu local configuration -- this file is NOT modified by plugin updates --
 * Make here your modifications
 *
 * See for information about available variables, menu structure, override and adding menu entries in the scripts/contextmenu.js
 */



/**
 * Right Context Menu configuration for all users:
 */
if (!indexmenu_contextmenu['all']['pg']) indexmenu_contextmenu['all']['pg'] = {'view': [] };
if (!indexmenu_contextmenu['all']['ns']) indexmenu_contextmenu['all']['ns'] = {'view': [] };


// Override title of page menu
//indexmenu_contextmenu['all']['pg']['view'][0] = ['Custom Title'];

// add option to page menu
//indexmenu_contextmenu['all']['pg']['view'].splice(1, 0, ['Input new page', '"javascript: indexmenu_reqpage(\'"+index.config.urlbase+"\',\'"+index.config.sepchar+"\',\'"+node.dokuid+"\');"']);


if (JSINFO && JSINFO.isadmin) {
    if (!indexmenu_contextmenu['pg']) indexmenu_contextmenu['pg'] = {'view': []};
    if (!indexmenu_contextmenu['ns']) indexmenu_contextmenu['ns'] = {'view': []};
    /**
     * Right Context Menu configuration for admin users:
     */

    //override or add here the menu entries for admin, see for examples above


} else if (JSINFO && JSINFO.isauth) {
    if (!indexmenu_contextmenu['pg']) indexmenu_contextmenu['pg'] = {'view': []};
    if (!indexmenu_contextmenu['ns']) indexmenu_contextmenu['ns'] = {'view': []};
    /**
     * Right Context Menu configuration for authenticated users:
     */

    //override or add here the menu entries for authenticated users, see for examples above

}

/**
 * Common available functions:
 *
 * Some common functions are added by [indexmenu plugin folcer]/scripts/contextmenu.js
 *  - indexmenu_srchpage(u, s, isdir, nid)
 *  - indexmenu_getid(u, id)
 *  - indexmenu_reqpage(b, s, id, n)
 *
 * Insert your custom functions (available for all users) at the bottom of this file.
 */

/**
 * Random Example function do something
 *
 * @param {string}   id
 * @param {Boolean}  isdir
 * @return {Boolean} true if nice, false is nothing
 */
/*
function indexmenu_custom_dosomething(a, isdir) {
   //do something
   return false;
}
*/