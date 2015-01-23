<?php

namespace fkooman\RelMeAuth;

use Guzzle\Http\Client;
use fkooman\Http\Uri;

class RelMeFetcher
{
    /* @var Guzzle\Http\Client */
    private $client;

    public function __construct(Client $client = null)
    {
        if (null === $client) {
            $client = new Client();
        }
        $this->client = $client;
    }

    public function fetchRel($profileUrl)
    {
        $u = new Uri($profileUrl);

        $profilePage = $this->client->get($profileUrl)->send()->getBody();

        // retrieve the supported rels
        $htmlParser = new HtmlParser();
        $meLinks = $htmlParser->getRelLinks($profilePage);

        return $this->filterProviders($meLinks);
    }

    private function filterProviders(array $meLinks)
    {
        // we only support GitHub now
        $supportedProviders = array();
        foreach ($meLinks as $meLink) {
            $meLinkUri = new Uri($meLink);
            switch ($meLinkUri->getHost()) {
                case 'github.com':
                    $supportedProviders['GitHub'] = $meLink;
                    break;
                default:
            }
        }

        return $supportedProviders;
    }
}
