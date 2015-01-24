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

use PDO;

class PdoStorage
{
    /** @var PDO */
    private $db;

    /** @var string */
    private $prefix;

    public function __construct(PDO $db, $prefix = '')
    {
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db = $db;
        $this->prefix = $prefix;
    }

    public function storeIndieState($provider, $me, $clientId, $redirectUri, $state)
    {
        $stmt = $this->db->prepare(
            sprintf(
                'INSERT INTO %s (provider, me, client_id, redirect_uri, state) VALUES(:provider, :me, :client_id, :redirect_uri, :state)',
                $this->prefix.'indie_states'
            )
        );
        $stmt->bindValue(':provider', $provider, PDO::PARAM_STR);
        $stmt->bindValue(':me', $me, PDO::PARAM_STR);
        $stmt->bindValue(':client_id', $clientId, PDO::PARAM_STR);
        $stmt->bindValue(':redirect_uri', $redirectUri, PDO::PARAM_STR);
        $stmt->bindValue(':state', $state, PDO::PARAM_STR);
        $stmt->execute();

        if (1 !== $stmt->rowCount()) {
            throw new PdoStorageException('unable to add');
        }
    }

    public function getIndieState($state)
    {
        $stmt = $this->db->prepare(
            sprintf(
                'SELECT * FROM %s WHERE state = :state',
                $this->prefix.'indie_states'
            )
        );
        $stmt->bindValue(':state', $state, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $this->db->prepare(
            sprintf(
                'DELETE FROM %s WHERE state = :state',
                $this->prefix.'indie_states'
            )
        );
        $stmt->bindValue(':state', $state, PDO::PARAM_STR);
        $stmt->execute();

        if (1 === $stmt->rowCount()) {
            // state was deleted, return the result
            return $result;
        }

        return false;
    }

    public function storeIndieCode($provider, $me, $clientId, $redirectUri, $code)
    {
        $stmt = $this->db->prepare(
            sprintf(
                'INSERT INTO %s (provider, me, client_id, redirect_uri, code) VALUES(:provider, :me, :client_id, :redirect_uri, :code)',
                $this->prefix.'indie_codes'
            )
        );
        $stmt->bindValue(':provider', $provider, PDO::PARAM_STR);
        $stmt->bindValue(':me', $me, PDO::PARAM_STR);
        $stmt->bindValue(':client_id', $clientId, PDO::PARAM_STR);
        $stmt->bindValue(':redirect_uri', $redirectUri, PDO::PARAM_STR);
        $stmt->bindValue(':code', $code, PDO::PARAM_STR);
        $stmt->execute();

        if (1 !== $stmt->rowCount()) {
            throw new PdoStorageException('unable to add');
        }
    }

    public function getIndieCode($code)
    {
        $stmt = $this->db->prepare(
            sprintf(
                'SELECT * FROM %s WHERE code = :code',
                $this->prefix.'indie_codes'
            )
        );
        $stmt->bindValue(':code', $code, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $this->db->prepare(
            sprintf(
                'DELETE FROM %s WHERE code = :code',
                $this->prefix.'indie_codes'
            )
        );
        $stmt->bindValue(':code', $code, PDO::PARAM_STR);
        $stmt->execute();

        if (1 === $stmt->rowCount()) {
            // code was deleted, return the result
            return $result;
        }

        return false;
    }

    public function storeAccessToken($provider, $me, $accessToken)
    {
        $stmt = $this->db->prepare(
            sprintf(
                'INSERT INTO %s (provider, me, access_token) VALUES(:provider, :me, :access_token)',
                $this->prefix.'oauth_tokens'
            )
        );
        $stmt->bindValue(':provider', $provider, PDO::PARAM_STR);
        $stmt->bindValue(':me', $me, PDO::PARAM_STR);
        $stmt->bindValue(':access_token', $accessToken, PDO::PARAM_STR);
        $stmt->execute();

        if (1 !== $stmt->rowCount()) {
            throw new PdoStorageException('unable to add');
        }
    }

    public function getAccessToken($provider, $me)
    {
        $stmt = $this->db->prepare(
            sprintf(
                'SELECT access_token FROM %s WHERE provider = :provider AND me = :me',
                $this->prefix.'oauth_tokens'
            )
        );
        $stmt->bindValue(':provider', $provider, PDO::PARAM_STR);
        $stmt->bindValue(':me', $me, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function deleteAccessToken($provider, $me, $accessToken)
    {
        $stmt = $this->db->prepare(
            sprintf(
                'DELETE FROM %s WHERE provider = :provider AND me = :me AND access_token = :access_token',
                $this->prefix.'oauth_tokens'
            )
        );
        $stmt->bindValue(':provider', $provider, PDO::PARAM_STR);
        $stmt->bindValue(':me', $me, PDO::PARAM_STR);
        $stmt->bindValue(':access_token', $accessToken, PDO::PARAM_STR);
        $stmt->execute();

        if (1 !== $stmt->rowCount()) {
            throw new PdoStorageException('unable to delete');
        }
    }
    
    public static function createTableQueries($prefix)
    {
        $query = array();

        $query[] = sprintf(
            'CREATE TABLE IF NOT EXISTS %s (
                state VARCHAR(255) NOT NULL,
                provider VARCHAR(255) NOT NULL,
                me VARCHAR(255) NOT NULL,
                client_id VARCHAR(255) NOT NULL,
                redirect_uri VARCHAR(255) NOT NULL,
                PRIMARY KEY (state)
            )',
            $prefix.'indie_states'
        );

        $query[] = sprintf(
            'CREATE TABLE IF NOT EXISTS %s (
                code VARCHAR(255) NOT NULL,
                provider VARCHAR(255) NOT NULL,
                me VARCHAR(255) NOT NULL,
                client_id VARCHAR(255) NOT NULL,
                redirect_uri VARCHAR(255) NOT NULL,
                PRIMARY KEY (code)
            )',
            $prefix.'indie_codes'
        );

        $query[] = sprintf(
            'CREATE TABLE IF NOT EXISTS %s (
                provider VARCHAR(255) NOT NULL,
                me VARCHAR(255) NOT NULL,
                access_token VARCHAR(255) NOT NULL,
                UNIQUE (provider, me)
            )',
            $prefix.'oauth_tokens'
        );

        return $query;
    }

    public function initDatabase()
    {
        $queries = self::createTableQueries($this->prefix);
        foreach ($queries as $q) {
            $this->db->query($q);
        }

        $tables = array('indie_states', 'indie_codes', 'oauth_tokens');
        foreach ($tables as $t) {
            // make sure the tables are empty
            $this->db->query(
                sprintf(
                    'DELETE FROM %s',
                    $this->prefix.$t
                )
            );
        }
    }
}
