/**
 * The Indexmenu Wizard
 *
 * @author Gerrit Uitslag
 * based on Linkwiz by
 * @author Andreas Gohr <gohr@cosmocode.de>
 * @author Pierre Spring <pierre.spring@caillou.ch>
 * and the concepts of the old indexmenu wizard
 */
var indexmenu_wiz = {
    $wiz: null,
    timer: null,
    textArea: null,

    defaulttheme: 'default',
    fields: {
        div1: {
            elems: {
                js: {}
            }
        },
        div2: {
            tlbclass: 'js theme',
            elems: {
                el1: {headerid: 'theme'}
            }
        },
        div3: {
            elems: {
                el2: {headerid: 'navigation'},
                navbar: {},
                context: {},
                nocookie: {tlbclass: 'js'},
                noscroll: {tlbclass: 'js'},
                notoc: {tlbclass: 'js'}
            }
        },
        div4: {
            elems: {
                el3: {headerid: 'sort'},
                tsort: {},
                dsort: {},
                msort: {},
                hsort: {},
                rsort: {},
                nsort: {}
            }
        },
        div5: {
            elems: {
                el4: {headerid: 'filter'},
                nons: {},
                nopg: {}
            }
        },
        div6: {
            tlbclass: 'js',
            elems: {
                el5: {headerid: 'performance'},
                max: {tlbclass: 'js', number: ['maxn', 'maxm']},
                maxjs: {tlbclass: 'js', number: ['maxjsn']},
                id: {tlbclass: 'js', number: ['idn']}
            }
        }
    },

    /**
     * Initialize the indexmenu_wiz by creating the needed HTML
     * and attaching the eventhandlers
     */
    init: function($editor){
        // position relative to the text area
        var pos = $editor.position();

        // create HTML Structure
        indexmenu_wiz.$wiz = jQuery(document.createElement('div'))
            .dialog({
                autoOpen: false,
                draggable: true,
                title: LANG.plugins.indexmenu.indexmenuwizard,
                resizable: false
            })
            .html(
                '<fieldset class="index"><legend>'+LANG.plugins.indexmenu.index+'</legend>' +
                    '<div><label>'+LANG.plugins.indexmenu.namespace+'<input id="namespace" type="text"></label></div>' +
                    '<div><label class="number">'+LANG.plugins.indexmenu.nsdepth+' #<input id="nsdepth" type="text" value=1></label></div>' +
                '</fieldset>' +

                '<fieldset class="options"><legend>'+LANG.plugins.indexmenu.options+'</legend>' +
                '</fieldset>' +
                '<input type="submit" value="'+LANG.plugins.indexmenu.insert+'" class="button" id="indexmenu__insert">'+

                '<fieldset class="metanumber">' +
                    '<label class="number">'+LANG.plugins.indexmenu.metanum+'<input type="text" id="metanumber"></label>' +
                    '<input type="submit" value="'+LANG.plugins.indexmenu.insertmetanum+'" class="button" id="indexmenu__insertmetanum">' +
                '</fieldset>'
            )
            .parent()
            .attr('id','indexmenu__wiz')
            .css({
                'position':    'absolute',
                'top':         (pos.top+20)+'px',
                'left':        (pos.left+80)+'px'
            })
            .hide()
            .appendTo('.dokuwiki:first');

        indexmenu_wiz.textArea = $editor[0];
        var $opt_fieldset = jQuery('#indexmenu__wiz fieldset.options');

        jQuery.each(indexmenu_wiz.fields, function(i,section) {
            var div = jQuery('<div>').addClass(section.tlbclass);

            jQuery.each(section.elems, function(elid,props){
                if(props.headerid) {
                    div.append('<strong>'+LANG.plugins.indexmenu[props.headerid]+'</strong><br />');
                } else {
                    //checkbox
                    jQuery("<label>")
                        .addClass(props.tlbclass).addClass(props.number ? ' num':'')
                        .html('<input id="'+elid+'" type="checkbox">'+elid)
                        .attr({title: LANG.plugins.indexmenu[elid]})
                        .appendTo(div);

                    //number inputs
                    if(props.number) {
                        jQuery.each(props.number, function(j,numid){
                             jQuery("<label>")
                                .attr({title: LANG.plugins.indexmenu[elid]})
                                .addClass("number "+props.tlbclass )
                                .html('#<input type="text" id="'+numid+'">')
                                .appendTo(div);
                        });
                    }
                }
            });
            $opt_fieldset.append(div);
        });

        indexmenu_wiz.includeThemes();

        if(JSINFO && JSINFO.namespace){
            jQuery('#namespace').val(':'+JSINFO.namespace);
        }

        // attach event handlers

        //toggle js fields
        jQuery('#js')
            .change(function(){
                jQuery('#indexmenu__wiz .js').toggle(this.checked);
            }).change()
            .parent().css({display: 'inline-block', width: '40px'}); //enlarge clickable area of label

        //interactive number fields
        jQuery('label.number input').bind('keydown keyup', function(){
            //allow only numbers
            indexmenu_wiz.filterNumberinput(this);
            //checked the option if a number in input
            indexmenu_wiz.autoCheckboxForNumbers(this);
        });

        jQuery('#indexmenu__insert').click(indexmenu_wiz.insertIndexmenu);
        jQuery('#indexmenu__insertmetanum').click(indexmenu_wiz.insertMetaNumber);

        jQuery('#indexmenu__wiz').find('.ui-dialog-titlebar-close').click(indexmenu_wiz.hide);
    },

    /**
     * Request and include themes in wizard
     */
    includeThemes: function() {

        var addButtons = function(data) {
            var $themebar = jQuery('<div>')
                .attr('id','themebar')
                .addClass('toolbar')
                .appendTo('div.theme');

            jQuery.each(data.themes, function(i, theme){
                var themeName =theme.split('.');

                var $ico = jQuery('<div>')
                    .css({ background: 'url('+ DOKU_BASE + data.themebase + '/' + theme + '/base.' + IndexmenuUtils.determineExtension(theme)+') no-repeat center' });
                var $btn = jQuery('<button>')
                    .addClass('themebutton toolbutton')
                    .attr('id', theme)
                    .attr('title', themeName[0])
                    .append($ico)
                    .click(indexmenu_wiz.selectTheme)
                    .appendTo('div#themebar');
            });

            //select default theme
            jQuery('#themebar button#'+indexmenu_wiz.defaulttheme).click();
        };

        jQuery.post(
            DOKU_BASE + 'lib/exe/ajax.php',
            {call: 'indexmenu', req: 'local'},
            addButtons,
            'json'
        );
    },

    /**
     * set class 'selected' to clicked theme, remove from other
     */
    selectTheme: function(){
        jQuery('.themebutton').toggleClass('selected', false);
        jQuery(this).toggleClass('selected', true);
    },

    /**
     * Allow only number, by direct removing other characters from input
     */
    filterNumberinput: function(elem){
        if(elem.value.match(/\D/)) {
            elem.value=this.value.replace(/\D/g,'');
        }
    },

    /**
     * When a number larger than zero is inputted, check the checkbox
     */
    autoCheckboxForNumbers: function(elem){
        var checkboxid = elem.id.substr(0,elem.id.length-1);
        var value = elem.value;
        //exception for second number field of max: only uncheck when first field is also empty
        if(elem.id=='maxm' && !(elem.value>0)) {
            value = parseInt(jQuery('input#maxn').val());
        }
        jQuery('input#'+checkboxid).prop('checked', value>0 );
    },

    /**
     * Insert the indexmenu with options to the textarea,
     * replacing the current selection or at the cursor position.
     */
    insertIndexmenu: function(){
        var options = '';
        jQuery('fieldset.options input').each(function(i, input){
            var $label = jQuery(this).parent();

            if(input.checked && ( !$label.hasClass('js')||jQuery('input#js').is(':checked') )){
                //add option
                options += ' '+input.id;

                //add numbers
                if($label.hasClass('num')){
                    jQuery.each(indexmenu_wiz.fields.div6.elems[input.id].number, function(j,numid){
                        var num = parseInt(jQuery('input#'+numid).val());
                        options +=  num ? '#'+num : '';
                    });
                }
                //add theme
                if(input.id == 'js') {
                    var themename = jQuery('#themebar button.selected').attr('id');
                    if(indexmenu_wiz.defaulttheme !== themename) { //skip default theme
                        options += '#'+jQuery('#themebar button.selected').attr('id');
                    }

                }
            }

        });
        options = options ? '|'+jQuery.trim(options) : '';

        var sel, ns, depth, syntax, eo;
        
        // XXX: Compatibility Fix for 2014-05-05 "Ponder Stibbons", splitbrain/dokuwiki#505
        if(DWgetSelection) {
            sel = DWgetSelection(indexmenu_wiz.textArea);
        } else {
            sel = getSelection(indexmenu_wiz.textArea);
        }
        

        ns = jQuery('#namespace').val();
        depth = parseInt(jQuery('#nsdepth').val());
        depth = depth ? '#'+depth : '';

        syntax = '{{indexmenu>'+ns+depth+options+'}}';
        eo = depth.length + options.length + 2;

        pasteText(sel, syntax,{startofs: 12, endofs: eo});
        indexmenu_wiz.hide();
    },

    /**
     * Insert meta number for sorting in textarea
     * Takes number from input, otherwise tries the selection in textarea
     */
    insertMetaNumber: function(){
        var sel, selnum, syntax, number;

        // XXX: Compatibility Fix for 2014-05-05 "Ponder Stibbons", splitbrain/dokuwiki#505
        if(DWgetSelection) {
            sel = DWgetSelection(indexmenu_wiz.textArea);
        } else {
            sel = getSelection(indexmenu_wiz.textArea);
        }

        selnum = parseInt(sel.getText());
        number = parseInt(jQuery('input#metanumber').val());
        number = number || selnum || 1;
        syntax = '{{indexmenu_n>'+number+'}}';

        pasteText(sel, syntax,{startofs: 14, endofs: 2});
        indexmenu_wiz.hide();
    },

    /**
     * Show the indexmenu wizard
     */
    show: function(){
        // XXX: Compatibility Fix for 2014-05-05 "Ponder Stibbons", splitbrain/dokuwiki#505
        if(DWgetSelection) {
            indexmenu_wiz.selection = DWgetSelection(indexmenu_wiz.textArea);
        } else {
            indexmenu_wiz.selection = getSelection(indexmenu_wiz.textArea);
        }

        indexmenu_wiz.$wiz.show();
        jQuery('#namespace').focus();
    },

    /**
     * Hide the indexmenu wizard
     */
    hide: function(){
        indexmenu_wiz.$wiz.hide();
        indexmenu_wiz.textArea.focus();
    },

    /**
     * Toggle the indexmenu wizard
     */
    toggle: function(){
        if(indexmenu_wiz.$wiz.css('display') == 'none'){
            indexmenu_wiz.show();
        }else{
            indexmenu_wiz.hide();
        }
    }
};
