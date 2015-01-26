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
use fkooman\Http\Exception\BadRequestException;
use fkooman\Http\Exception\ForbiddenException;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\ClientErrorResponseException;

class GitHub
{
    /** @var string */
    private $clientId;
        
    /** @var string */
    private $clientSecret;

    /** @var fkooman\Http\Session */
    private $session;

    /** @var Guzzle\Http\Client */
    private $client;

    const ACCESS_TOKEN_URI = 'https://github.com/login/oauth/access_token';
    const USER_API_URI = 'https://api.github.com/user';

    public function __construct($clientId, $clientSecret, Session $session = null, Client $client = null)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;

        if (null === $session) {
            $session = new Session('GitHub');
        }
        $this->session = $session;

        if (null === $client) {
            $client = new Client();
        }
        $this->client = $client;
    }

    public function authorizeRequest($me)
    {
        $state = bin2hex(
            openssl_random_pseudo_bytes(16)
        );

        $this->session->setValue('github_me', $me);
        $this->session->setValue('github_state', $state);

        return new RedirectResponse(
            sprintf(
                'https://github.com/login/oauth/authorize?client_id=%s&state=%s',
                $this->clientId,
                $state
            ),
            302
        );
    }

    public function handleCallback(Request $request)
    {
        $state = $request->getQueryParameter('state');
        $code = $request->getQueryParameter('code');

        $sessionMe = $this->session->getValue('github_me');
        $this->session->deleteKey('github_me');
        $sessionState = $this->session->getValue('github_state');
        $this->session->deleteKey('github_state');

        if ($state !== $sessionState) {
            throw new BadRequestException('callback state value does not match authorize state');
        }

        // request an access token
        $response = $this->client->post(
            GitHub::ACCESS_TOKEN_URI
        )->setHeader(
            'Accept',
            'application/json'
        )->setPostField(
            'client_id',
            $this->clientId
        )->setPostField(
            'client_secret',
            $this->clientSecret
        )->setPostField(
            'code',
            $code
        )->send()->json();

        // get the user data
        $response = $this->client->get(
            GitHub::USER_API_URI
        )->setHeader(
            'Authorization',
            sprintf('Bearer %s', $response['access_token'])
        )->send()->json();

        if ($response['blog'] !== $sessionMe) {
            throw new ForbiddenException('profile URL does not match expected value');
        }
    }
}
