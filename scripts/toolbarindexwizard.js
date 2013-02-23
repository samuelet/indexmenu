/**
 * Created with IntelliJ IDEA.
 * User: gerrit
 * Date: 18-2-13
 * Time: 16:17
 * To change this template use File | Settings | File Templates.
 */


/**
 * The Indexmenu Wizard
 *
 * @author Andreas Gohr <gohr@cosmocode.de>
 * @author Pierre Spring <pierre.spring@caillou.ch>
 */
var indexmenu_wiz = {
    $wiz: null,
    timer: null,
    textArea: null,

    fields: {
        div1: {
            elems: {
                js: {}
            }
        },
        div2: {
            class: 'js theme',
            elems: {
                el1: {headerid: 'theme'}
            }
        },
        div3: {
            elems: {
                el2: {headerid: 'navigation'},
                navbar: {},
                context: {},
                nocookie: {class: 'js'},
                noscroll: {class: 'js'},
                notoc: {class: 'js'}
            }
        },
        div4: {
            elems: {
                el3: {headerid: 'sort'},
                tsort: {},
                dsort: {},
                msort: {},
                nsort: {},
                rsort: {}
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
            class: 'js',
            elems: {
                el5: {headerid: 'performance'},
                max: {class: 'js', number: ['maxn', 'maxm']},
                maxjs: {class: 'js', number: ['maxjsn']},
                id: {class: 'js', number: ['idn']}
            }
        }
    },

    /**
     * Initialize the dw_linkwizard by creating the needed HTML
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
            var div = jQuery('<div>').addClass(section.class);

            jQuery.each(section.elems, function(elid,props){
                if(props.headerid) {
                    div.append('<strong>'+LANG.plugins.indexmenu[props.headerid]+'</strong><br />');
                } else {
                    //checkbox
                    jQuery("<label>")
                        .addClass(props.class).addClass(props.number ? ' num':'')
                        .html('<input id="'+elid+'" type="checkbox">'+elid)
                        .attr({title: LANG.plugins.indexmenu[elid]})
                        .appendTo(div);

                    //number inputs
                    if(props.number) {
                        jQuery.each(props.number, function(j,numid){
                             jQuery("<label>")
                                .attr({title: LANG.plugins.indexmenu[elid]})
                                .addClass("number "+props.class )
                                .html('#<input type="text" id="'+numid+'">')
                                .appendTo(div);
                        });
                    }
                }
            });
            $opt_fieldset.append(div);
        });

        if(JSINFO.namespace){
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

        jQuery('#indexmenu__wiz .ui-dialog-titlebar-close').click(indexmenu_wiz.hide);
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

            if(input.checked && ( !$label.hasClass('js')||jQuery('#indexmenu__wiz input#js').is(':checked') )){
                options += ' '+input.id;

                if($label.hasClass('num')){
                    jQuery.each(indexmenu_wiz.fields.div6.elems[input.id].number, function(j,numid){
                        var num = parseInt(jQuery('input#'+numid).val());
                        options +=  num ? '#'+num : '';
                    });
                }
            }

        });
        options = options ? '|'+jQuery.trim(options) : '';

        var sel, ns, depth, syntax, eo;
        sel = getSelection(indexmenu_wiz.textArea);

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
        sel = getSelection(indexmenu_wiz.textArea);
        selnum = parseInt(sel.getText());
        number = parseInt(jQuery('input#metanumber').val());
        number = number || selnum || 1;
        syntax = '{{indexmenu_n>'+number+'}}';

        pasteText(sel, syntax,{startofs: 14, endofs: 2});
        indexmenu_wiz.hide();
    },

    /**
     * Show the link wizard
     */
    show: function(){
        indexmenu_wiz.selection  = getSelection(indexmenu_wiz.textArea);
        indexmenu_wiz.$wiz.show();
        //indexmenu_wiz.$entry.focus();
        //indexmenu_wiz.autocomplete();
    },

    /**
     * Hide the link wizard
     */
    hide: function(){
        indexmenu_wiz.$wiz.hide();
        indexmenu_wiz.textArea.focus();
        console.log('hide');
    },

    /**
     * Toggle the link wizard
     */
    toggle: function(){
        if(indexmenu_wiz.$wiz.css('display') == 'none'){
            indexmenu_wiz.show();
        }else{
            indexmenu_wiz.hide();
        }
    }
};