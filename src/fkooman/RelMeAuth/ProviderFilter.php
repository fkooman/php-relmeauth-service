<?php

namespace fkooman\RelMeAuth;

use fkooman\Http\Uri;
use fkooman\Http\UriException;

class ProviderFilter
{

    public function filter(array $meLinks)
    {
        // we only support GitHub now
        $supportedProviders = array();
        foreach ($meLinks as $meLink) {
            try {
                $meLinkUri = new Uri($meLink);
                if ('https' !== $meLinkUri->getScheme()) {
                    // ignore non https URIs
                    continue;
                }
                switch ($meLinkUri->getHost()) {
                    case 'github.com':
                        $supportedProviders['GitHub'] = $meLink;
                        break;
                    default:
                }
            } catch (UriException $e) {
                // ignore invalid URLs
            }
        }

        return $supportedProviders;
    }
}
