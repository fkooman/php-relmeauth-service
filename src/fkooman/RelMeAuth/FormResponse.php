<?php

/**
* Copyright 2015 François Kooman <fkooman@tuxed.net>
*
* Licensed under the Apache License, Version 2.0 (the "License");
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
*
* http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
*/

namespace fkooman\RelMeAuth;

use fkooman\Http\Response;

class FormResponse extends Response
{
    public function __construct($statusCode = 200)
    {
        parent::__construct($statusCode, 'application/x-www-form-urlencoded');
    }

    public function setContent($content)
    {
        if(null === $content) {
            parent::setContent(null);
        } else {
            parent::setContent(
                http_build_query($content)
            );
        }
    }

    public function getContent()
    {
        $content = parent::getContent();
        parse_str($content, $parsedFormString);

        return $parsedFormString;
    }
}
