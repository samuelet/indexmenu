<?php

/**
 * Info Indexmenu tag: Tag a page with a sort number.
 *
 * @license     GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author      Samuele Tognini <samuele@samuele.netsons.org>
 *
 */

if(!defined('DOKU_INC')) die();

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_indexmenu_tag extends DokuWiki_Syntax_Plugin {

    /**
     * What kind of syntax are we?
     */
    function getType() {
        return 'substition';
    }

    /**
     * Where to sort in?
     */
    function getSort() {
        return 139;
    }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('{{indexmenu_n>.+?}}', $mode, 'plugin_indexmenu_tag');
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler) {
        $match = substr($match, 14, -2);
        $match = str_replace("\xE2\x80\x8B", "", $match);
        return array($match);
    }

    /**
     * Render output
     */
    function render($mode, Doku_Renderer $renderer, $data) {
        if($mode == 'metadata') {
            /** @var Doku_Renderer_metadata $renderer */
            if(is_numeric($data[0])) $renderer->meta['indexmenu_n'] = $data[0];
        }
    }
}