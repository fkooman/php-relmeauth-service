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

use fkooman\Http\RedirectResponse;
use fkooman\Http\Request;
use fkooman\Http\Session;
use Guzzle\Http\Client;
use fkooman\X509\CertParser;
use fkooman\X509\CertParserException;
use fkooman\Http\Exception\ForbiddenException;
use fkooman\Http\Exception\BadRequestException;

class WebId
{
    /** @var fkooman\Http\Session */
    private $session;

    /** @var Guzzle\Http\Client */
    private $client;

    public function __construct(Session $session = null, Client $client = null)
    {
        if (null === $session) {
            $session = new Session('WebId');
        }
        $this->session = $session;

        if (null === $client) {
            $client = new Client();
        }
        $this->client = $client;
    }

    public function authorizeRequest($me)
    {
        return new RedirectResponse(
            // FIXME: do not hard code the callback URI
            'https://indie.tuxed.net/php-relmeauth-service/callback',
            302
        );
    }

    public function handleCallback(Request $request)
    {
        $clientCert = $request->getHeader('SSL_CLIENT_CERT');
        if (null === $clientCert) {
            throw new BadRequestException('no certificate provided');
        }
        $certParser = new CertParser($clientCert);
        $certFingerprint = sprintf(
            'di:sha-256;%s?ct=application/x-x509-user-cert',
            $certParser->getFingerPrint('sha256', true)
        );

        $supportedProviders = $this->session->getValue('supported_providers');

        if (!in_array($certFingerprint, $supportedProviders['WebId'])) {
            throw new ForbiddenException(
                sprintf(
                    'fingerprint does not match, we expected to find "%s"',
                    $certFingerprint
                )
            );
        }
    }
}
