<?php

namespace fkooman\RelMeAuth;

use fkooman\Http\Request;
use fkooman\Http\Response;
use fkooman\Rest\Service;
use Twig_Loader_Filesystem;
use Twig_Environment;
use Guzzle\Http\Client;
use fkooman\Http\Uri;

class RelMeAuthService extends Service
{
    /** @var fkooman\RelMeAuth\PdoStorage */
    private $pdoStorage;

    private $gitHub;

    public function __construct(PdoStorage $pdoStorage, GitHub $gitHub, Client $client = null)
    {
        parent::__construct();

        $this->pdoStorage = $pdoStorage;
        $this->gitHub = $gitHub;

        if (null === $client) {
            $client = new Client();
        }
        $this->client = $client;
   
        // in PHP 5.3 we cannot use $this from a closure
        $compatThis = &$this;

        $this->get(
            '/auth',
            function (Request $request) {
                // first validate the request
                $me = $request->getQueryParameter('me');
                $clientId = $request->getQueryParameter('client_id');
                $redirectUri = $request->getQueryParameter('redirect_uri');
                // FIXME: none must be null

                $meUri = new Uri($me);
                $clientIdUri = new Uri($clientId);
                $redirectUriUri = new Uri($redirectUri);

                if ('https' !== $meUri->getScheme()) {
                    throw new Exception('need https for meUri');
                }
                // client_id and redirect_uri need to both be https
                //if ('https' !== $clientIdUri->getScheme() || 'https' !== $redirectUriUri->getScheme()) {
                //    throw new Exception('need https for client_id and redirect_uri');
                //}
                // and both MUST have the same domain name
                if ($clientIdUri->getHost() !== $redirectUriUri->getHost()) {
                    throw new Exception('host names of client_id and redirect_uri do not match');
                }
                // initial validation complete

                $relMeFetcher = new RelMeFetcher($this->client);
                $relMeFetcher->fetchRel($me);

                $loader = new Twig_Loader_Filesystem(
                    dirname(dirname(dirname(__DIR__))).'/views'
                );
                $twig = new Twig_Environment($loader);

                $providerSelector = $twig->render(
                    'providerSelector.twig',
                    array(
                        //'configs' => $configs,
                        //'templateData' => $this->templateData,
                    )
                );
                $response = new Response();
                $response->setContent($providerSelector);
                return $response;
            }
        );

        $this->post(
            '/auth',
            function (Request $request) {
                //$s = new Session('RelMeAuth', false);
                // CSRF protection by validating the referer!

                $me = $request->getQueryParameter('me');
                $clientId = $request->getQueryParameter('client_id');
                $redirectUri = $request->getQueryParameter('redirect_uri');

                $selectedProvider = $request->getPostParameter('selectedProvider');

                // user chose a provider
                // return the correct provider instance shizzle
                return $this->gitHub->verifyProfileUrl($me, $clientId, $redirectUri);
            }
        );

        $this->get(
            '/callback',
            function (Request $request) {
                // how to determine the actual provider used? we need to somehow get that from the DB first
                return $this->gitHub->callbackRequest($request->getQueryParameter('state'), $request->getQueryParameter('code'));
            }
        );

        $this->post(
            '/verify',
            function (Request $request) {
                $code = $request->getPostParameter('code');
                $clientId = $request->getPostParameter('client_id');
                $redirectUri = $request->getPostParameter('redirect_uri');

                // how to determine the actual provider used?
                $verifiedMe = $this->gitHub->getVerifiedMe($clientId, $redirectUri, $code);

                $response = new Response(200, 'application/x-www-form-urlencoded;charset=utf-8');
                $response->setContent(
                    http_build_query(
                        array(
                            'me' => $verifiedMe
                        )
                    )
                );

                return $response;
            }
        );
    }
}
