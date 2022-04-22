<?php

/**
 * Info Indexmenu title: custom title
 *
 * @license     GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author      ZhangMing
 *
 */

if(!defined('DOKU_INC')) die();

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_indexmenu_customtitle extends DokuWiki_Syntax_Plugin {

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
        return 140;
    }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('{{indexmenu_title>.+?}}', $mode, 'plugin_indexmenu_customtitle');
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler) {
        $match = substr($match, 18, -2);
        return array($match);
    }

    /**
     * Render output
     */
    function render($mode, Doku_Renderer $renderer, $data) {
        if($mode == 'metadata') {
            /** @var Doku_Renderer_metadata $renderer */
            $renderer->meta['indexmenu_title'] = $data[0];
        }
    }
}