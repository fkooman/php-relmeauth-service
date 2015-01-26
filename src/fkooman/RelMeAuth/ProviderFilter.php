<?php

/**
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace fkooman\RelMeAuth;

use fkooman\Http\Uri;
use fkooman\Http\Exception\UriException;

class ProviderFilter
{

    public function filter(array $meLinks)
    {
        $supportedProviders = array();
        foreach ($meLinks as $meLink) {
            try {
                // WebID
                if (preg_match('/^x509:[a-fA-F0-9]+$/', $meLink)) {
                    // fingerprint value
                    $supportedProviders['WebId'] = $meLink;
                    continue;
                }

                $meLinkUri = new Uri($meLink);
                if ('https' !== $meLinkUri->getScheme()) {
                    // ignore non https URIs
                    continue;
                }
                switch ($meLinkUri->getHost()) {
                    case 'github.com':
                        $supportedProviders['GitHub'] = $meLink;
                        break;
                    case 'twitter.com':
                        $supportedProviders['Twitter'] = $meLink;
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
