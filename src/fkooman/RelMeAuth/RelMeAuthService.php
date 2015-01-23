<?php

namespace fkooman\RelMeAuth;

use fkooman\Http\Request;
use fkooman\Http\Response;
use fkooman\Rest\Service;
use Twig_Loader_Filesystem;
use Twig_Environment;
use Guzzle\Http\Client;
use fkooman\Http\Uri;
use fkooman\Http\Exception\UriException;
use fkooman\Http\Exception\BadRequestException;
use fkooman\Http\Exception\ForbiddenException;

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
                $fullRequestUri = $request->getRequestUri()->getUri();
                $referrerUri = $request->getHeader("HTTP_REFERER");

                if ($fullRequestUri !== $referrerUri) {
                    throw new ForbiddenException(
                        "referrer does not match request URL"
                    );
                }

                $me = $request->getQueryParameter('me');
                $clientId = $request->getQueryParameter('client_id');
                $redirectUri = $request->getQueryParameter('redirect_uri');

                $selectedProvider = $request->getPostParameter('selectedProvider');
                // FIXME: make the selectedProvider actually do something

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

                $indieCode = $this->pdoStorage->getIndieCode($code);

                if ($clientId !== $indieCode['client_id']) {
                    throw new \Exception('non matching client_id');
                }
                 if ($redirectUri !== $indieCode['redirect_uri']) {
                     throw new \Exception('non matching redirect_uri');
                 }

                $response = new Response(200, 'application/x-www-form-urlencoded;charset=utf-8');
                $response->setContent(
                    http_build_query(
                        array(
                            'me' => $indieCode['me']
                        )
                    )
                );

                return $response;
            }
        );
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
