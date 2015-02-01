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
        $supportedProviders = array(
            'GitHub' => array(),
            'Twitter' => array()
        );

        foreach ($meLinks as $meLink) {
            // GitHub
            if (preg_match('/^https:\/\/github.com/', $meLink)) {
                $supportedProviders['GitHub'][] = $meLink;
                continue;
            }
            // Twitter
            if (preg_match('/^https:\/\/twitter.com/', $meLink)) {
                $supportedProviders['Twitter'][] = $meLink;
                continue;
            }
        }

        return $supportedProviders;
    }
}
