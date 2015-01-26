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

use fkooman\Http\Exception\ForbiddenException;
use fkooman\Http\RedirectResponse;
use fkooman\Http\Request;
use fkooman\Http\Session;
use fkooman\Json\Json;
use Guzzle\Http\Client;
use OAuth;

class Twitter
{
    /** @var string */
    private $clientId;
        
    /** @var string */
    private $clientSecret;

    /** @var fkooman\Http\Session */
    private $session;

    /** @var Guzzle\Http\Client */
    private $client;

    public function __construct($clientId, $clientSecret, Session $session = null, Client $client = null)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;

        if (null === $session) {
            $session = new Session('Twitter');
        }
        $this->session = $session;

        if (null === $client) {
            $client = new Client();
        }
        $this->client = $client;

        $this->oauth = new OAuth(
            $this->clientId,
            $this->clientSecret,
            OAUTH_SIG_METHOD_HMACSHA1,
            OAUTH_AUTH_TYPE_URI
        );
    }

    public function authorizeRequest($me)
    {
        $request_token_info = $this->oauth->getRequestToken(
            'https://twitter.com/oauth/request_token'
        );

        $this->session->setValue('twitter_me', $me);
        $this->session->setValue('twitter_oauth_token_secret', $request_token_info['oauth_token_secret']);

        return new RedirectResponse(
            sprintf('https://twitter.com/oauth/authenticate?oauth_token=%s', $request_token_info["oauth_token"]),
            302
        );
    }

    public function handleCallback(Request $request)
    {
        $oauthToken = $request->getQueryParameter('oauth_token');

        $sessionMe = $this->session->getValue('twitter_me');
        $this->session->deleteKey('twitter_me');
        $sessionOauthTokenSecret = $this->session->getValue('twitter_oauth_token_secret');
        $this->session->deleteKey('twitter_oauth_token_secret');

        $this->oauth->setToken($oauthToken, $sessionOauthTokenSecret);
        $access_token_info = $this->oauth->getAccessToken('https://twitter.com/oauth/access_token');

        $this->oauth->setToken($access_token_info["oauth_token"], $access_token_info["oauth_token_secret"]);
        $this->oauth->fetch('https://api.twitter.com/1.1/account/verify_credentials.json');

        $response = Json::decode(
            $this->oauth->getLastResponse()
        );

        // follow link found in API response to get around "t.co" shortener
        $profileUrl = $this->client->get($response['url'])->send()->getInfo('url');

        if ($profileUrl !== $sessionMe) {
            throw new ForbiddenException('profile URL does not match expected value');
        }
    }
}
