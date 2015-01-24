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

use PHPUnit_Framework_TestCase;

class HtmlParserTest extends PHPUnit_Framework_TestCase
{

    public function testFkooman()
    {
        $htmlParser = new HtmlParser();
        $this->assertEquals(
            array(
                'https://twitter.com/fkooman',
                'https://github.com/fkooman',
                'https://facebook.com/fkooman',
                'https://nl.linkedin.com/in/fkooman'
            ),
            $htmlParser->getRelLinks(file_get_contents(dirname(dirname(__DIR__)).'/data/fkooman.html'))
        );
    }

    public function testMichiel()
    {
        $htmlParser = new HtmlParser();
        $this->assertEquals(
            array(
                'https://twitter.com/michielbdejong',
            ),
            $htmlParser->getRelLinks(file_get_contents(dirname(dirname(__DIR__)).'/data/michiel.html'))
        );
    }

    public function testAaron()
    {
        $htmlParser = new HtmlParser();
        $this->assertEquals(
            array(
                'mailto:aaron@parecki.com',
                'sms:+15035678642',
                'sms:+15035678642',
                'fax:+15038946046',
                'sms:+19712753884',
                'mailto:aaron@parecki.com',
                'https://github.com/aaronpk',
                'https://twitter.com/aaronpk',
                'https://instagram.com/aaronpk',
                'http://flickr.com/aaronpk',
                'http://www.linkedin.com/in/aaronparecki',
                'https://alpha.app.net/aaronpk',
                'http://facebook.com/aaronpk',
                'http://foursquare.com/aaronpk',
                'http://plancast.com/aaronpk',
                'http://lanyrd.com/people/aaronpk',
                'http://geoloqi.com/aaronpk',
                'http://www.slideshare.net/aaronpk',
                'https://www.beeminder.com/aaronpk',
                'http://www.last.fm/user/aaron_pk',
                'https://keybase.io/aaronpk/'
            ),
            $htmlParser->getRelLinks(file_get_contents(dirname(dirname(__DIR__)).'/data/aaron.html'))
        );
    }

    public function testElf()
    {
        $htmlParser = new HtmlParser();
        $this->assertEquals(
            array(
                'mailto:perpetual-tripper@wwelves.org',
                'xmpp:perpetual-tripper@wwelves.org',
                'https://github.com/elf-pavlik',
                'https://twitter.com/elfpavlik',
                'https://plus.google.com/+elfPavlik',
                'https://facebook.com/elf.pavlik'
            ),
            $htmlParser->getRelLinks(file_get_contents(dirname(dirname(__DIR__)).'/data/elf.html'))
        );
    }
}
