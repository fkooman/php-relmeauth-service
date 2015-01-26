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

use fkooman\Http\Request;
use fkooman\Http\Response;
use fkooman\Http\FormResponse;
use fkooman\Rest\Service;
use Twig_Loader_Filesystem;
use Twig_Environment;
use Guzzle\Http\Client;
use fkooman\Http\Uri;
use fkooman\Http\Session;
use fkooman\Http\RedirectResponse;
use fkooman\Http\Exception\UriException;
use fkooman\Http\Exception\BadRequestException;
use fkooman\Http\Exception\ForbiddenException;

class RelMeAuthService extends Service
{
    /** @var array */
    private $providers;

    /** @var fkooman\RelMeAuth\PdoStorage */
    private $pdoStorage;

    /** @var fkooman\Http\Session */
    private $session;

    /** @var Guzzle\Http\Client */
    private $client;

    public function __construct(array $providers, PdoStorage $pdoStorage, Session $session = null, Client $client = null)
    {
        parent::__construct();

        $this->providers = $providers;
        $this->pdoStorage = $pdoStorage;

        if (null === $session) {
            $session = new Session('RelMeAuth');
        }
        $this->session = $session;

        if (null === $client) {
            $client = new Client();
        }
        $this->client = $client;
   
        // in PHP 5.3 we cannot use $this from a closure
        $compatThis = &$this;

        $this->get(
            '/auth',
            function (Request $request) use ($compatThis) {
                return $compatThis->getAuth($request);
            }
        );

        $this->post(
            '/auth',
            function (Request $request) use ($compatThis) {
                return $compatThis->postAuth($request);
            }
        );

        $this->get(
            '/callback',
            function (Request $request) use ($compatThis) {
                return $compatThis->getCallback($request);
            }
        );

        $this->post(
            '/verify',
            function (Request $request) use ($compatThis) {
                return $compatThis->postVerify($request);
            }
        );
    }

    public function getAuth(Request $request)
    {
        // first validate the request
        $this->validateQueryParameters($request);

        $relMeFetcher = new RelMeFetcher($this->client);
        $relLinks = $relMeFetcher->fetchRel(
            $request->getQueryParameter('me')
        );

        // filter out the providers we do not support
        $providerFilter = new ProviderFilter();
        $supportedProviders = $providerFilter->filter($relLinks);

        $loader = new Twig_Loader_Filesystem(
            dirname(dirname(dirname(__DIR__))).'/views'
        );
        $twig = new Twig_Environment($loader);

        $providerSelector = $twig->render(
            'providerSelector.twig',
            array(
                'supportedProviders' => $supportedProviders
            )
        );
        $response = new Response();
        $response->setContent($providerSelector);
        return $response;
    }

    public function postAuth(Request $request)
    {
        $fullRequestUri = $request->getRequestUri()->getUri();
        $referrerUri = $request->getHeader("HTTP_REFERER");

        if ($fullRequestUri !== $referrerUri) {
            throw new ForbiddenException(
                "referrer does not match request URL"
            );
        }

        // verify the parameters again (although the referrer should
        // take care of it in the normal case, it would still possible
        // to manually POST to this URL with invalid values
        $this->validateQueryParameters($request);

        $me = $request->getQueryParameter('me');
        $clientId = $request->getQueryParameter('client_id');
        $redirectUri = $request->getQueryParameter('redirect_uri');

        $selectedProvider = $request->getPostParameter('selectedProvider');

        $this->session->setValue('me', $me);
        $this->session->setValue('client_id', $clientId);
        $this->session->setValue('redirect_uri', $redirectUri);
        $this->session->setValue('selected_provider', $selectedProvider);

        $p = $this->providers[$selectedProvider];

        return $p->authorizeRequest($me);
    }

    public function getCallback(Request $request)
    {
        $me = $this->session->getValue('me');
        $clientId = $this->session->getValue('client_id');
        $redirectUri = $this->session->getValue('redirect_uri');
        $selectedProvider = $this->session->getValue('selected_provider');

        $p = $this->providers[$selectedProvider];
        $p->handleCallback($request);

        // create indiecode
        $code = bin2hex(openssl_random_pseudo_bytes(16));
        $this->pdoStorage->storeIndieCode($me, $clientId, $redirectUri, $code);

        // redirect back to app
        return new RedirectResponse(sprintf('%s?code=%s', $redirectUri, $code), 302);
    }

    public function postVerify(Request $request)
    {
        $code = $request->getPostParameter('code');
        $clientId = $request->getPostParameter('client_id');
        $redirectUri = $request->getPostParameter('redirect_uri');

        // FIXME: all these parameters above are required, if any of them
        // is missing we should throw a 400 bad request

        $indieCode = $this->pdoStorage->getIndieCode($code);

        if (false === $indieCode) {
            $response = new FormResponse(404);
            $response->setContent(
                array(
                    'error' => 'invalid_request',
                    'error_description' => 'the code provided was not valid',
                )
            );
            return $response;
        }

        if ($clientId !== $indieCode['client_id']) {
            throw new \Exception('non matching client_id');
        }
        if ($redirectUri !== $indieCode['redirect_uri']) {
            throw new \Exception('non matching redirect_uri');
        }

        $response = new FormResponse();
        $response->setContent(
            array(
                'me' => $indieCode['me']
            )
        );
        return $response;
    }

    private function validateQueryParameters(Request $request)
    {
        // we must have 'me', 'client_id' and 'redirect_uri' and they all
        // must be valid (HTTPS) URLs and the host of the client_id and
        // redirect_uri must match
        $requiredParameters = array('me', 'client_id', 'redirect_uri');
        foreach ($requiredParameters as $p) {
            $qp = $request->getQueryParameter($p);
            if (null === $qp) {
                throw new BadRequestException(
                    sprintf('missing parameter "%s"', $p)
                );
            }
            try {
                $u = new Uri($qp);
                if ('https' !== $u->getScheme()) {
                    throw new BadRequestException(
                        sprintf('URL must be HTTPS for "%s"', $p)
                    );
                }
            } catch (UriException $e) {
                throw new BadRequestException(
                    sprintf('invalid URL for "%s"', $p)
                );
            }
        }
        $clientId = new Uri($request->getQueryParameter('client_id'));
        $redirectUri = new Uri($request->getQueryParameter('redirect_uri'));
        if ($clientId->getHost() !== $redirectUri->getHost()) {
            throw new BadRequestException(
                'host for client_id and redirect_uri must match'
            );
        }
    }
}
