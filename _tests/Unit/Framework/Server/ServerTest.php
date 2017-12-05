<?php
/**
 * @author           Pierre-Henry Soria <hello@ph7cms.com>
 * @copyright        (c) 2017-2018, Pierre-Henry Soria. All Rights Reserved.
 * @license          GNU General Public License; See PH7.LICENSE.txt and PH7.COPYRIGHT.txt in the root directory.
 * @package          PH7 / Test / Unit / Framework / Server
 */

namespace PH7\Test\Unit\Framework\Server;

use PH7\Framework\Server\Server;
use PHPUnit_Framework_TestCase;

class ServerTest extends PHPUnit_Framework_TestCase
{
    public function testGetServerName()
    {
        $_SERVER['SERVER_NAME'] = 'Apache';

        $this->assertSame('Apache', Server::getName());
    }

    public function testItIsLocalHost()
    {
        $_SERVER['HTTP_HOST'] = '127.0.0.1';

        $this->assertTrue(Server::isLocalHost());
    }

    public function testItIsNotLocalHost()
    {
        $_SERVER['HTTP_HOST'] = 'ph7cms.com';

        $this->assertFalse(Server::isLocalHost());
    }

    public function testGetUndefinedServerKey()
    {
        $sActual = Server::getVar('UNDEFINED');

        $this->assertNull($sActual);
    }

    public function testGetUndefinedServerKeyWithDefaultValue()
    {
        $sActual = Server::getVar('UNDEFINED', 'My default value');

        $this->assertSame('My default value', $sActual);
    }

    public function testGetDefinedServerKey()
    {
        $_SERVER['SOMETHING'] = "<b>I'm the value</b>";
        $sActual = Server::getVar('SOMETHING');

        $this->assertSame('&lt;b&gt;I&#039;m the value&lt;/b&gt;', $sActual);
    }

    protected function tearDown()
    {
        unset($_SERVER);
    }
}