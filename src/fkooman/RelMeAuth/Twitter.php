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
use OAuth;
use Guzzle\Http\Client;

class Twitter
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
        $sessionOauthTokenSecret = $this->session->getValue('twitter_oauth_token_secret');

        $this->oauth->setToken($oauthToken, $sessionOauthTokenSecret);
        $access_token_info = $this->oauth->getAccessToken('https://twitter.com/oauth/access_token');

        $this->pdoStorage->storeTwitterToken(
            $sessionMe,
            $access_token_info["oauth_token"],
            $access_token_info["oauth_token_secret"]
        );
    }

    public function verifyProfileUrl($me)
    {
        $twitterToken = $this->pdoStorage->getTwitterToken($me);
        if (false === $twitterToken) {
            return false;
        }

        try {
            $this->oauth->setToken($twitterToken["oauth_token"], $twitterToken["oauth_token_secret"]);
            $this->oauth->fetch('https://api.twitter.com/1.1/account/verify_credentials.json');

            $response = json_decode($this->oauth->getLastResponse(), true);

            $profileUrl = $response['url'];
            // we need to follow the t.co link because Twitter conveniently decides to t.co it...
            $r = $this->client->get($profileUrl)->send();
            $profileUrl = $r->getInfo('url');

            // FIXME: if it does not match, should we delete the access token?
            if ($profileUrl === $me) {
                return true;
            }

            throw new \Exception('expected profile url not found');
        } catch (OAuthException $e) {
            //            $this->pdoStorage->deleteTwitterToken($me);

#            if (401 === $e->getResponse()->getStatusCode()) {
#                $this->pdoStorage->deleteGitHubToken($me, $accessToken['access_token']);
#                return false;
#            }
            throw $e;
        }
    }
}
