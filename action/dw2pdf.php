<?php

use dokuwiki\Extension\ActionPlugin;
use dokuwiki\Extension\Event;
use dokuwiki\Extension\EventHandler;

/**
 * Class action_plugin_indexmenu_dw2pdf
 */
class action_plugin_indexmenu_dw2pdf extends ActionPlugin
{
    /**
     * @var bool Strict mode requires a tag for all sorted pages
     */
    protected bool $isStrictMode;

    /**
     * @var array Page ids and their corresponding ordering tags
     */
    protected array $tags = [];

    /**
     * @param EventHandler $controller DokuWiki's event controller object.
     */
    public function register(EventHandler $controller)
    {
        $controller->register_hook('DW2PDF_NAMESPACEEXPORT_SORT', 'BEFORE', $this, 'sortPages2Pdf');
    }

    /**
     * Triggered by dw2pdf plugin.
     * Custom sorting of pages if "book_order" parameter was set to "indexmenu" or "indexmenu_strict"
     *
     * @param Event $event
     * @return true
     */
    public function sortPages2Pdf(Event $event)
    {
        $sort = $event->data['sort'];
        if ($sort === 'indexmenu' || $sort === 'indexmenu_strict') {
            $this->isStrictMode = str_contains($sort, 'strict');
            $event->preventDefault();

            $pages =& $event->data['pages'];

            // extract tags for easier testing
            foreach ($pages as $page) {
                $this->tags[$page['id']] = p_get_metadata($page['id'], 'indexmenu_n');
                // break early in strict mode if tags are missing
                if ($this->isStrictMode && is_null($this->tags[$page['id']])) {
                    throw new Exception('Page ' . $page['id'] . ' does not exist or does not have an indexmenu tag!');
                }
            }

            usort($pages, [$this, 'cbIndexmenuSort']);
        }

        return true;
    }

    /**
     * usort callback to sort by indexmenu tag.
     * Whole namespaces are sorted: start pages define the sort order on the same ns level.
     *
     * @param array $a Page info in event data
     * @param array $b Page info in event data
     * @return int
     */
    public function cbIndexmenuSort($a, $b)
    {
        global $conf;

        $partsA = explode(':', $a['id']);
        $partsB = explode(':', $b['id']);

        //find where the namespaces diverge
        [$nsA, $nsB] = $this->getFirstDifferentNs($partsA, $partsB);

        // pages in the same namespace
        if (is_null($nsA) && is_null($nsB)) {
            // start page always has priority
            if ($partsA[count($partsA) - 1] === $conf['start']) return -1;
            if ($partsB[count($partsB) - 1] === $conf['start']) return 1;

            // otherwise compare via indexmenu tag
            return $this->tagCompare($a['id'], $b['id']);
        }

        // one of the pages is in a sub-namespace
        if (is_null($nsA)) return -1;
        if (is_null($nsB)) return 1;

        // different namespaces, so first resolve the page holding the actual sorting order for this level
        $idA = $this->resolveSortingAnchor($partsA, $nsA);
        $idB = $this->resolveSortingAnchor($partsB, $nsB);

        return $this->tagCompare($idA, $idB);
    }

    /**
     * Compare ids based on indexmenu tag. If tags are missing or equal, do string comparison.
     *
     * @param string $idA
     * @param string $idB
     * @return int
     */
    public function tagCompare($idA, $idB)
    {
        $indexA = $this->tags[$idA];
        $indexB = $this->tags[$idB];

        if (is_null($indexA) || is_null($indexB) || $indexA === $indexB) {
            return strnatcmp($idA, $idB);
        }

        return $indexA <=> $indexB;
    }

    /**
     * Returns first different namespaces when comparing two arrays of namespace parts
     *
     * @param array $a Full id exploded by ":"
     * @param array $b Full id exploded by ":"
     * @return array
     */
    public function getFirstDifferentNs($a, $b)
    {
        $countA = count($a);
        $countB = count($b);
        $max = max($countA, $countB);

        for ($i = 0; $i < $max - 1; $i++) {
            $partA = $a[$i] ?: null;
            $partB = $b[$i] ?: null;

            if ($i === $countA - 1) {
                return [null, $partB];
            }

            if ($i === $countB - 1) {
                return [$partA, null];
            }

            if ($partA !== $partB) {
                return [$partA, $partB];
            }
        }

        return [null, null];
    }

    /**
     * Resolve the id of the page that is relevant for sorting (anchor).
     * When comparing pages in different namespaces, it is necessary to reference the start page,
     * because tags are used for sorting ACROSS and WITHIN namespaces.
     *
     * @param array $parts Full id exploded by ":"
     * @param string $ns
     * @return string
     */
    public function resolveSortingAnchor($parts, $ns)
    {
        global $conf;

        $path_parts = [];
        foreach ($parts as $part) {
            $path_parts[] = $part;

            // we hit the target namespace, append the start page and return immediately
            if ($part === $ns) {
                $path_parts[] = $conf["start"];
                return implode(':', $path_parts);
            }
        }

        // fallback
        return implode(':', $parts);
    }
}
