<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Http;

use Mockery;
use PHPUnit_Framework_TestCase;
use QL\Panthor\Testing\Stub\StringableStub;
use QL\Panthor\Utility\Json;

class EncryptedCookiesTest extends PHPUnit_Framework_TestCase
{
    public $slim;
    public $json;
    public $encryption;

    public function setUp()
    {
        $this->slim = Mockery::mock('Slim\Slim');
        $this->json = new Json;
        $this->encryption = Mockery::mock('QL\Panthor\Http\CookieEncryptionInterface');
    }

    public function testDeleteCookieParamsPassThroughToSlim()
    {
        $this->slim
            ->shouldReceive('deleteCookie')
            ->with('name', null, '*.domain.com')
            ->once();

        $cookies = new EncryptedCookies($this->slim, $this->json, $this->encryption);

        $cookies->deleteCookie('name', null, '*.domain.com');
    }

    public function testSetCookieParamsPassThroughToSlim()
    {
        $this->slim
            ->shouldReceive('setCookie')
            ->with('name', 'testvalue', 0, '/page', '.domain.com')
            ->once();

        $cookies = new EncryptedCookies($this->slim, $this->json, $this->encryption);

        $cookies->setCookie('name', 'testvalue', 0, '/page', '.domain.com');
    }

    public function testGetCookieDecryptsCookieFromSlim()
    {
        $this->slim
            ->shouldReceive('getCookie')
            ->with('name')
            ->andReturn('encryptedvalue');

        $this->encryption
            ->shouldReceive('decrypt')
            ->with('encryptedvalue')
            ->andReturn('"decryptedvalue"');

        $cookies = new EncryptedCookies($this->slim, $this->json, $this->encryption);

        $actual = $cookies->getCookie('name');

        $this->assertSame('decryptedvalue', $actual);
    }

    public function testInvalidCookieDeletesByDefaultAndReturnsNull()
    {
        $this->slim
            ->shouldReceive('getCookie')
            ->with('name')
            ->andReturn('encryptedvalue');

        $this->encryption
            ->shouldReceive('decrypt')
            ->with('encryptedvalue')
            ->andReturnNull();

        $this->slim
            ->shouldReceive('deleteCookie')
            ->with('name')
            ->once();

        $cookies = new EncryptedCookies($this->slim, $this->json, $this->encryption);

        $actual = $cookies->getCookie('name');
        $this->assertNull($actual);
    }

    public function testResponseCookiesAreEncryptedWhenRetrieved()
    {
        $this->encryption
            ->shouldReceive('encrypt')
            ->with('"cookievalue1"')
            ->andReturn('encrypted-cookievalue1');
        $this->encryption
            ->shouldReceive('encrypt')
            ->with('"cookievalue2"')
            ->andReturn('encrypted-cookievalue2');

        $cookies = new EncryptedCookies($this->slim, $this->json, $this->encryption);

        // prime the data
        $cookies['cookie1'] = ['value' => 'cookievalue1'];
        $cookies['cookie2'] = ['value' => 'cookievalue2'];
        $cookies['cookie3'] = ['value' => null];

        $responseCookies = [];
        foreach ($cookies as $name => $value) {
            $responseCookies[$name] = $value['value'];
        }

        $expected = [
            'cookie1' => 'encrypted-cookievalue1',
            'cookie2' => 'encrypted-cookievalue2',
            'cookie3' => null
        ];

        $this->assertSame($expected, $responseCookies);
    }

    public function testCookieIsAutomaticallyJsonSerialized()
    {
        $data = ['bing', 'bong'];
        $dataJsonified = '["bing","bong"]';

        $this->encryption
            ->shouldReceive('encrypt')
            ->with($dataJsonified)
            ->andReturn('encrypted-value')
            ->once();

        $cookies = new EncryptedCookies($this->slim, $this->json, $this->encryption);

        // prime the data
        $cookies['cookie1'] = ['value' => $data];

        $cookie = $cookies->getResponseCookie('cookie1');
        $expected = 'encrypted-value';
        $this->assertSame($expected, $cookie['value']);
    }

    public function testCookieIsAutomaticallyJsonDeserialized()
    {
        // $data = ['bing', 'bong'];
        $dataBadlyJsonified = '["bing","bong"}';

        $this->slim
            ->shouldReceive('getCookie')
            ->with('cookie1')
            ->andReturn('encrypted');

        $this->encryption
            ->shouldReceive('decrypt')
            ->with('encrypted')
            ->andReturn($dataBadlyJsonified)
            ->once();

        $cookies = new EncryptedCookies($this->slim, $this->json, $this->encryption);

        $cookie = $cookies->getCookie('cookie1');

        // Badly formatted json is passed back, rather than decoded json.
        $this->assertSame($dataBadlyJsonified, $cookie);
    }

    public function testCookieFailsDecryptionButIsAllowUnencrypted()
    {
        $data = ['bing', 'bong'];
        $dataJsonified = '["bing","bong"]';

        $this->slim
            ->shouldReceive('getCookie')
            ->with('cookie1')
            ->andReturn($dataJsonified);

        $this->encryption
            ->shouldReceive('decrypt')
            ->with($dataJsonified)
            ->andReturnNull()
            ->once();

        $cookies = new EncryptedCookies($this->slim, $this->json, $this->encryption, ['cookie1']);

        $cookie = $cookies->getCookie('cookie1');
        $this->assertSame($data, $cookie);
    }

    public function testCookieFailsDecryptionButIsAllowUnencryptedAndThenAlsoFailsDecodingIsDeleted()
    {
        $dataBadlyJsonified = '["bing","bong"}';

        $this->slim
            ->shouldReceive('getCookie')
            ->with('cookie1')
            ->andReturn($dataBadlyJsonified);

        $this->encryption
            ->shouldReceive('decrypt')
            ->with($dataBadlyJsonified)
            ->andReturnNull()
            ->once();

        $this->slim
            ->shouldReceive('deleteCookie')
            ->with('cookie1')
            ->once();

        $cookies = new EncryptedCookies($this->slim, $this->json, $this->encryption, ['cookie1']);

        $cookie = $cookies->getCookie('cookie1');
        $this->assertSame(null, $cookie);
    }

    public function testMissingCookieReturnsNull()
    {
        $cookies = new EncryptedCookies($this->slim, $this->json, $this->encryption);

        $cookie = $cookies->getResponseCookie('cookie1');
        $this->assertSame(null, $cookie);
    }
}
