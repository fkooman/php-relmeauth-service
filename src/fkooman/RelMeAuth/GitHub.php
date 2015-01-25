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
use Guzzle\Http\Exception\ClientErrorResponseException;

class GitHub
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

        $sessionState = $this->session->getValue('github_state');
        $this->session->deleteKey('github_state');
        $sessionMe = $this->session->getValue('github_me');
        $this->session->deleteKey('github_me');

        if ($state !== $sessionState) {
            // FIXME: do we need to make sure the returned statevalue is not null or some other bogus value?
            throw new \Exception('state in callback does not match session state');
        }

        // request access_token
        $response = $this->client->post(
            'https://github.com/login/oauth/access_token'
        )->setPostField('client_id', $this->clientId)
        ->setHeader('Accept', 'application/json')
        ->setPostField('client_secret', $this->clientSecret)
        ->setPostField('code', $code)
        ->send()->json();

        // store access_token
        //echo $sessionMe . $response['access_token'];
        $this->pdoStorage->storeGitHubToken($sessionMe, $response['access_token']);
    }

    public function verifyProfileUrl($me)
    {
        $accessToken = $this->pdoStorage->getGitHubToken($me);
        if (false === $accessToken) {
            return false;
        }

        try {
            $response = $this->client->get(
                'https://api.github.com/user'
            )->setHeader(
                'Authorization',
                sprintf('Bearer %s', $accessToken['access_token'])
            )->send()->json();

            // FIXME: if it does not match, should we delete the access token?
            if ($response['blog'] === $me) {
                return true;
            }

            throw new \Exception('expected profile url not found');
        } catch (ClientErrorResponseException $e) {
            if (401 === $e->getResponse()->getStatusCode()) {
                $this->pdoStorage->deleteGitHubToken($me);
                return false;
            }
            throw $e;
        }
    }
}
