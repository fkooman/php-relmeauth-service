<?php

namespace fkooman\RelMeAuth;

use DomDocument;
use fkooman\Http\Uri;
use fkooman\Http\Exception\UriException;

class HtmlParser
{
    private $dom;

    public function __construct()
    {
        $this->dom = new DomDocument();
    }

    public function getRelLinks($htmlString)
    {
        // disable error handling by DomDocument so we handle them ourselves
        libxml_use_internal_errors(true);
        $this->dom->loadHTML($htmlString);
        // throw away all errors, we do not care about them anyway
        libxml_clear_errors();

        $tags = array('link', 'a');
        $links = array();
        foreach ($tags as $tag) {
            $elements = $this->dom->getElementsByTagName($tag);
            foreach ($elements as $element) {
                $href = $element->getAttribute('href');
                $rel = $element->getAttribute('rel');
                if ('me' !== $rel) {
                    continue;
                }
                $links[] = $href;
            }
        }
        return $links;
    }
}
