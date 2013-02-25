/**
 * Right Context Menu configuration for admin users:
 *
 * Menu is built from four array items: title, link, show if page or headpage, show if namespace.
 * Link is not created if it's 0, otherwise it's evaluated.
 * Second array is displayed only in edit mode.
 *
 * Some usefull variables:
 *   node.hns = headpage id;
 *   node.isdir = node is namespace;
 *   node.dokuid = the DW id (namespace parent in case of headpage);
 *   id = the DW id of the selected node (headpage id in case of headpage);
 *   index.config.urlbase = Url Base;
 *   index.config.sepchar = Url separator;
 */
indexmenu_contextmenu['pg'] = {
    'view': [
        ['New page here', '"javascript: indexmenu_reqpage(\'"+index.config.urlbase+"\',\'"+index.config.sepchar+"\',\'"+node.dokuid+"\');"'],
        ['Edit', 'indexmenu_getid(index.config.urlbase,id)+"do=edit"', 1, 0 ],
        ['<em>More--></em>', [
            ['Headpage here', '"javascript: indexmenu_reqpage(\'"+index.config.urlbase+"\',\'"+index.config.sepchar+"\',\'"+node.dokuid+"\',\'"+node.name+"\');"'],
            ['Purge cache', 'indexmenu_getid(index.config.urlbase,id)+"purge=true"'],
            ['Export as HTML', 'indexmenu_getid(index.config.urlbase,id)+"do=export_xhtml"']
        ]]
    ]
};
