<?php
/**
 * @copyright (c) 2015 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Twig;

use DateTime;
use DateTimeZone;
use Mockery;
use PHPUnit_Framework_TestCase;
use QL\MCP\Common\Time\TimePoint;
use QL\Panthor\Utility\Url;
use stdClass;

class TwigExtensionTest extends PHPUnit_Framework_TestCase
{
    public $url;
    public $clock;

    public function setUp()
    {
        $this->url = Mockery::mock(Url::CLASS);
        $this->clock = Mockery::mock(Clock::CLASS);
    }

    public function testName()
    {
        $ext = new TwigExtension($this->url, false);
        $this->assertSame('panthor', $ext->getName());
    }

    public function testIsDebugMode()
    {
        $ext = new TwigExtension($this->url, true);
        $this->assertSame(true, $ext->isDebugMode());
    }

    public function testGetFunctionsDoesNotBlowUp()
    {
        $ext = new TwigExtension($this->url, false);
        $this->assertInternalType('array', $ext->getFunctions());
    }

    public function testGetFiltersDoesNotBlowUp()
    {
        $ext = new TwigExtension($this->url, false);
        $this->assertInternalType('array', $ext->getFilters());
    }

    public function testGetTimepointReadsFromClock()
    {
        $time = Mockery::mock(TimePoint::CLASS);
        $this->clock
            ->shouldReceive('read')
            ->andReturn($time);

        $ext = new TwigExtension($this->url, $this->clock, 'America\Detroit', false);
        $this->assertSame($time, $ext->getTimepoint());
    }

    public function testGetTimepointReadsFromClockAndModifies()
    {
        $time = Mockery::mock(TimePoint::CLASS);
        $time
            ->shouldReceive('modify')
            ->with('+2 days')
            ->once();

        $this->clock
            ->shouldReceive('read')
            ->andReturn($time);

        $ext = new TwigExtension($this->url, $this->clock, 'America\Detroit', false);
        $this->assertSame($time, $ext->getTimepoint('+2 days'));
    }

    public function testFormattingDateAcceptsDateTime()
    {
        $expected = '2014-08-05 11:00:32';
        $datetime = new DateTime('2014-08-05 15:00:32', new DateTimeZone('UTC'));
        $ext = new TwigExtension($this->url, $this->clock, 'America/Detroit', false);

        $this->assertSame($expected, $ext->formatTimepoint($datetime, 'Y-m-d H:i:s'));
    }

    public function testFormattingDateAcceptsTimePoint()
    {
        $expected = '2014-08-05 15:00:32';
        $timepoint = new TimePoint(2014, 8, 5, 15, 0, 32, 'America/Detroit');
        $ext = new TwigExtension($this->url, $this->clock, 'America/Detroit', false);

        $this->assertSame($expected, $ext->formatTimepoint($timepoint, 'Y-m-d H:i:s'));
    }

    public function testFormattingDateFailsGracefullyWithUnknownType()
    {
        $invalid = new stdClass;
        $ext = new TwigExtension($this->url, $this->clock, 'America/Detroit', false);

        $this->assertSame('', $ext->formatTimepoint($invalid, 'Y-m-d H:i:s'));
    }
}
