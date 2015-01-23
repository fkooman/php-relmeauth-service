<?php

namespace fkooman\RelMeAuth;

use Guzzle\Http\Client;

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
        $profilePage = $this->client->get($profileUrl)->send()->getBody();
        $htmlParser = new HtmlParser();
        return $htmlParser->getRelLinks($profilePage);
    }
}
