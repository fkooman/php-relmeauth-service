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

class WebId
{
    /** @var string */
    private $clientId;
        
    /** @var string */
    private $clientSecret;

    /** @var fkooman\RelMeAuth\PdoStorage */
    private $pdoStorage;

    /** @var fkooman\Http\Session */
    private $session;

    /** @var Guzzle\Http\Client */
    private $client;

    public function __construct($clientId, $clientSecret, PdoStorage $pdoStorage, Session $session, Client $client = null)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;

        $this->session = $session;
        $this->pdoStorage = $pdoStorage;

        if (null === $client) {
            $client = new Client();
        }
        $this->client = $client;
    }

    public function authorizeRequest($me)
    {
        return new RedirectResponse(
            // FIXME: not hard coded
            'https://indie.tuxed.net/php-relmeauth-service/index.php/callback',
            302
        );
    }

    public function handleCallback(Request $request)
    {
        $supportedProviders = $this->session->getValue('supported_providers');
        $meFingerprint = $supportedProviders['WebId'];

        $clientCert = $request->getHeader('SSL_CLIENT_CERT');
        try {
            $certParser = new CertParser($clientCert);
                        
            // match it with the 'WebID' => 'x509:xyz'
            // to use a 'proper' scheme to represent the hash...
            //    http://tools.ietf.org/html/draft-hallambaker-digesturi-02
            //    https://github.com/Spomky-Labs/base64url
            $certFingerprint = sprintf(
                'di:sha-256;%s?ct=application/x-x509-user-cert',
                $certParser->getFingerPrint('sha256', true)
            );

            //die($certFingerprint);

            if ($certFingerprint !== $meFingerprint) {
                throw new \Exception('fingerprint does not match');
            }
        } catch (CertParserException $e) {
            // FIXME: do something with this?
            throw $e;
        }
    }
}
