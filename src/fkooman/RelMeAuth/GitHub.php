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

use Guzzle\Http\Client;
use fkooman\Http\RedirectResponse;
use Guzzle\Http\Exception\ClientErrorResponseException;

class GitHub
{
    /** @var string */
    private $clientId;
        
    /** @var string */
    private $clientSecret;

    /** @var Guzzle\Http\Client */
    private $client;

    /** @var fkooman\RelMeAuth\PdoStorage */
    private $pdoStorage;

    public function __construct($clientId, $clientSecret, PdoStorage $pdoStorage, Client $client = null)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;

        $this->pdoStorage = $pdoStorage;

        if (null === $client) {
            $client = new Client();
        }
        $this->client = $client;
    }

    public function authorizeRequest($me, $clientId, $redirectUri)
    {
        $state = bin2hex(
            openssl_random_pseudo_bytes(16)
        );
        $this->pdoStorage->storeIndieState('GitHub', $me, $clientId, $redirectUri, $state);

        return new RedirectResponse(
            sprintf(
                'https://github.com/login/oauth/authorize?client_id=%s&state=%s',
                $this->clientId,
                $state
            ),
            302
        );
    }

    public function callbackRequest($state, $code)
    {
        $stateData = $this->pdoStorage->getIndieState($state);
        if (false === $stateData) {
            throw new \Exception('unable to find state value');
        }

        // request access_token
        $response = $this->client->post(
            'https://github.com/login/oauth/access_token'
        )->setPostField('client_id', $this->clientId)
        ->setHeader('Accept', 'application/json')
        ->setPostField('client_secret', $this->clientSecret)
        ->setPostField('code', $code)
        ->send()->json();

        var_dump($response);
        // store access_token
        $this->pdoStorage->storeAccessToken('GitHub', $stateData['me'], $response['access_token']);

        return $this->verifyProfileUrl($stateData['me'], $stateData['client_id'], $stateData['redirect_uri']);
    }

    public function verifyProfileUrl($me, $clientId, $redirectUri)
    {
        $accessToken = $this->pdoStorage->getAccessToken('GitHub', $me);
        if (false === $accessToken) {
            return $this->authorizeRequest($me, $clientId, $redirectUri);
        }

        try {
            $response = $this->client->get(
                'https://api.github.com/user'
            )->setHeader(
                'Authorization',
                sprintf('Bearer %s', $accessToken['access_token'])
            )->send()->json();

            if ($response['blog'] !== $me) {
                throw new \Exception('url does not match');
            }

            // store indie code
            $code = bin2hex(openssl_random_pseudo_bytes(16));
            $this->pdoStorage->storeIndieCode('GitHub', $me, $clientId, $redirectUri, $code);

            return new RedirectResponse(sprintf('%s?code=%s', $redirectUri, $code), 302);
        } catch (ClientErrorResponseException $e) {
            if (401 === $e->getResponse()->getStatusCode()) {
                $this->pdoStorage->deleteAccessToken('GitHub', $me, $accessToken['access_token']);

                return $this->authorizeRequest($me, $clientId, $redirectUri);
            }
            throw $e;
        }
    }
}
