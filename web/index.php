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

require_once dirname(__DIR__).'/vendor/autoload.php';

use fkooman\Http\Exception\HttpException;
use fkooman\Http\Exception\InternalServerErrorException;
use fkooman\RelMeAuth\RelMeAuthService;
use fkooman\RelMeAuth\PdoStorage;
use fkooman\Ini\IniReader;
use fkooman\RelMeAuth\GitHub;
use fkooman\Http\Session;
use Guzzle\Http\Client;

try {
    $iniReader = IniReader::fromFile(
        dirname(__DIR__).'/config/config.ini'
    );

    // STORAGE
    $pdo = new PDO(
        $iniReader->v('PdoStorage', 'dsn'),
        $iniReader->v('PdoStorage', 'username', false),
        $iniReader->v('PdoStorage', 'password', false)
    );
    $pdoStorage = new PdoStorage($pdo);

    // SESSION
    $session = new Session('RelMeAuth', false);

    // HTTP CLIENT
    $client = new Client();

    // PROVIDERS
    $supportedProviders = array(
        'GitHub' => new GitHub(
            $iniReader->v('GitHub', 'client_id'),
            $iniReader->v('GitHub', 'client_secret'),
            $pdoStorage,
            $session,
            $client
        )
    );

    $service = new RelMeAuthService($supportedProviders, $pdoStorage, $session, $client);
    $service->run()->sendResponse();
} catch (Exception $e) {
    if ($e instanceof HttpException) {
        $response = $e->getHtmlResponse();
    } else {
        // we catch all other (unexpected) exceptions and return a 500
        $e = new InternalServerErrorException($e->getMessage());
        $response = $e->getHtmlResponse();
    }
    $response->sendResponse();
}
