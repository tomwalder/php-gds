<?php
/**
 * Copyright 2023 Tom Walder
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Tests for exponential backoff
 *
 * @author Tom Walder <twalder@gmail.com>
 */
class BackoffTest extends \PHPUnit\Framework\TestCase
{
    public function testOnceAndReturn()
    {
        \GDS\Gateway::exponentialBackoff(true);
        $shouldBeCalled = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['__invoke'])
            ->getMock();
        $shouldBeCalled->expects($this->once())
            ->method('__invoke')
            ->willReturn(87);
        $int_result = $this->buildTestGateway()->runExecuteWithExponentialBackoff($shouldBeCalled);
        $this->assertEquals(87, $int_result);
    }

    public function testBackoffCount()
    {
        \GDS\Gateway::exponentialBackoff(true);
        $shouldBeCalled = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['__invoke'])
            ->getMock();
        $shouldBeCalled->expects($this->exactly(\GDS\Gateway::RETRY_MAX_ATTEMPTS))
            ->method('__invoke')
            ->willThrowException(new \RuntimeException('Test Exception', 503));
        $this->expectException(\RuntimeException::class);
        $this->buildTestGateway()->runExecuteWithExponentialBackoff($shouldBeCalled);
    }

    public function testBackoffCountDisabled()
    {
        \GDS\Gateway::exponentialBackoff(false);
        $shouldBeCalled = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['__invoke'])
            ->getMock();
        $shouldBeCalled->expects($this->once())
            ->method('__invoke')
            ->willThrowException(new \RuntimeException('Not retried', 503));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not retried');
        $this->expectExceptionCode(503);
        $this->buildTestGateway()->runExecuteWithExponentialBackoff($shouldBeCalled);
    }

    public function testPartialBackoff() {
        \GDS\Gateway::exponentialBackoff(true);
        $int_calls = 0;
        $shouldBeCalled = function () use (&$int_calls) {
            $int_calls++;
            if ($int_calls < 4) {
                throw new \RuntimeException('Always caught', 503);
            }
            return 42;
        };
        $int_result = $this->buildTestGateway()->runExecuteWithExponentialBackoff($shouldBeCalled);
        $this->assertEquals(42, $int_result);
        $this->assertEquals(4, $int_calls);
    }


    public function testIgnoredExceptionClass()
    {
        \GDS\Gateway::exponentialBackoff(true);
        $shouldBeCalled = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['__invoke'])
            ->getMock();
        $shouldBeCalled->expects($this->once())
            ->method('__invoke')
            ->willThrowException(new \LogicException('Ignored', 503));
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Ignored');
        $this->expectExceptionCode(503);
        $this->buildTestGateway()->runExecuteWithExponentialBackoff(
            $shouldBeCalled,
            \RuntimeException::class
        );
    }

    public function testIgnoredExceptionCode()
    {
        \GDS\Gateway::exponentialBackoff(true);
        $shouldBeCalled = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['__invoke'])
            ->getMock();
        $shouldBeCalled->expects($this->once())
            ->method('__invoke')
            ->willThrowException(new \RuntimeException('Non-retry code', 42));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Non-retry code');
        $this->expectExceptionCode(42);
        $this->buildTestGateway()->runExecuteWithExponentialBackoff($shouldBeCalled);
    }

    public function testRetryOnce()
    {
        \GDS\Gateway::exponentialBackoff(true);
        $int_calls = 0;
        $shouldBeCalled = function () use (&$int_calls) {
            $int_calls++;
            throw new \RuntimeException('Once', 500);
        };
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Once');
        $this->expectExceptionCode(500);
        $this->buildTestGateway()->runExecuteWithExponentialBackoff($shouldBeCalled);
        $this->assertEquals(2, $int_calls);
    }

    private function buildTestGateway(): \RESTv1GatewayBackoff
    {
        return new RESTv1GatewayBackoff('dataset-id', 'my-app');
    }
}
