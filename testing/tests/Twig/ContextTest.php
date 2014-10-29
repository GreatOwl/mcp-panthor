<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Panthor\Twig;

use PHPUnit_Framework_TestCase;

class ContextTest extends PHPUnit_Framework_TestCase
{
    public function testInitiallyLoadedContext()
    {
        $context = new Context([
            'test1' => 'value1',
            'test2' => ['test3' => 'value3']
        ]);

        $this->assertCount(2, $context);
    }

    public function testIterationOfContext()
    {
        $context = new Context([
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3'
        ]);

        $i = 0;
        foreach ($context as $val) {
            $expected = 'value' . ++$i;
            $this->assertSame($expected, $val);
        }
    }

    public function testMergingContextPreservesChildren()
    {
        $context = new Context([
            'test1' => 'value1',
            'test2' => ['test3' => 'value3']
        ]);

        $context->addContext([
            'test2' => ['test4' => 'value4']
        ]);


        $expected = [
            'test3' => 'value3',
            'test4' => 'value4'
        ];

        $this->assertSame($expected, $context->get('test2'));
    }

    public function testGetScenarios()
    {
        $raw = [
            'test1' => 'value1',
            'test2' => 'value2',
            'test3' => 'value3'
        ];

        $context = new Context($raw);

        $this->assertSame('value2', $context->get('test2'));
        $this->assertSame(null, $context->get('test4'));
        $this->assertSame($raw, $context->get());
    }
}
