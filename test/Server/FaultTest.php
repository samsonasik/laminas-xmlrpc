<?php

declare(strict_types=1);

namespace LaminasTest\XmlRpc\Server;

use DOMDocument;
use Laminas\XmlRpc\Server;
use Laminas\XmlRpc\Server\Fault;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function array_shift;
use function trim;

#[Group('Laminas_XmlRpc')]
class FaultTest extends TestCase
{
    /**
     * Laminas\XmlRpc\Server\Fault::getInstance() test
     */
    public function testGetInstance(): void
    {
        $e     = new Server\Exception\RuntimeException('Testing fault', 411);
        $fault = Server\Fault::getInstance($e);

        $this->assertInstanceOf(Fault::class, $fault);
    }

    /**
     * Laminas\XmlRpc\Server\Fault::attachFaultException() test
     */
    public function testAttachFaultException(): void
    {
        Server\Fault::attachFaultException(TestAsset\Exception::class);
        $e     = new TestAsset\Exception('test exception', 411);
        $fault = Server\Fault::getInstance($e);
        $this->assertEquals('test exception', $fault->getMessage());
        $this->assertEquals(411, $fault->getCode());
        Server\Fault::detachFaultException(TestAsset\Exception::class);

        $exceptions = [
            TestAsset\Exception::class,
            TestAsset\Exception2::class,
            TestAsset\Exception3::class,
        ];
        Server\Fault::attachFaultException($exceptions);
        foreach ($exceptions as $class) {
            $e     = new $class('test exception', 411);
            $fault = Server\Fault::getInstance($e);
            $this->assertEquals('test exception', $fault->getMessage());
            $this->assertEquals(411, $fault->getCode());
        }
        Server\Fault::detachFaultException($exceptions);
    }

    /**
     * Tests Laminas-1825
     */
    public function testAttachFaultExceptionAllowsForDerivativeExceptionClasses(): void
    {
        Server\Fault::attachFaultException(TestAsset\Exception::class);
        $e     = new TestAsset\Exception4('test exception', 411);
        $fault = Server\Fault::getInstance($e);
        $this->assertEquals('test exception', $fault->getMessage());
        $this->assertEquals(411, $fault->getCode());
        Server\Fault::detachFaultException(TestAsset\Exception::class);
    }

    /**
     * Laminas\XmlRpc\Server\Fault::detachFaultException() test
     */
    public function testDetachFaultException(): void
    {
        Server\Fault::attachFaultException(TestAsset\Exception::class);
        $e     = new TestAsset\Exception('test exception', 411);
        $fault = Server\Fault::getInstance($e);
        $this->assertEquals('test exception', $fault->getMessage());
        $this->assertEquals(411, $fault->getCode());
        Server\Fault::detachFaultException(TestAsset\Exception::class);
        $fault = Server\Fault::getInstance($e);
        $this->assertEquals('Unknown error', $fault->getMessage());
        $this->assertEquals(404, $fault->getCode());

        $exceptions = [
            TestAsset\Exception::class,
            TestAsset\Exception2::class,
            TestAsset\Exception3::class,
        ];
        Server\Fault::attachFaultException($exceptions);
        foreach ($exceptions as $class) {
            $e     = new $class('test exception', 411);
            $fault = Server\Fault::getInstance($e);
            $this->assertEquals('test exception', $fault->getMessage());
            $this->assertEquals(411, $fault->getCode());
        }
        Server\Fault::detachFaultException($exceptions);
        foreach ($exceptions as $class) {
            $e     = new $class('test exception', 411);
            $fault = Server\Fault::getInstance($e);
            $this->assertEquals('Unknown error', $fault->getMessage());
            $this->assertEquals(404, $fault->getCode());
        }
    }

    /**
     * Laminas\XmlRpc\Server\Fault::attachObserver() test
     */
    public function testAttachObserver(): void
    {
        Server\Fault::attachObserver(TestAsset\Observer::class);
        $e        = new Server\Exception\RuntimeException('Checking observers', 411);
        $fault    = Server\Fault::getInstance($e);
        $observed = TestAsset\Observer::getObserved();
        TestAsset\Observer::clearObserved();
        Server\Fault::detachObserver(TestAsset\Observer::class);

        $this->assertNotEmpty($observed);
        $f = array_shift($observed);
        $this->assertInstanceOf(Fault::class, $f);
        $this->assertEquals('Checking observers', $f->getMessage());
        $this->assertEquals(411, $f->getCode());

        $this->assertFalse(Server\Fault::attachObserver('foo'));
    }

    /**
     * Laminas\XmlRpc\Server\Fault::detachObserver() test
     */
    public function testDetachObserver(): void
    {
        Server\Fault::attachObserver(TestAsset\Observer::class);
        $e     = new Server\Exception\RuntimeException('Checking observers', 411);
        $fault = Server\Fault::getInstance($e);
        TestAsset\Observer::clearObserved();
        Server\Fault::detachObserver(TestAsset\Observer::class);

        $e        = new Server\Exception\RuntimeException('Checking observers', 411);
        $fault    = Server\Fault::getInstance($e);
        $observed = TestAsset\Observer::getObserved();
        $this->assertEmpty($observed);

        $this->assertFalse(Server\Fault::detachObserver('foo'));
    }

    /**
     * getCode() test
     */
    public function testGetCode(): void
    {
        $e     = new Server\Exception\RuntimeException('Testing fault', 411);
        $fault = Server\Fault::getInstance($e);

        $this->assertEquals(411, $fault->getCode());
    }

    /**
     * getException() test
     */
    public function testGetException(): void
    {
        $e     = new Server\Exception\RuntimeException('Testing fault', 411);
        $fault = Server\Fault::getInstance($e);

        $this->assertSame($e, $fault->getException());
    }

    /**
     * getMessage() test
     */
    public function testGetMessage(): void
    {
        $e     = new Server\Exception\RuntimeException('Testing fault', 411);
        $fault = Server\Fault::getInstance($e);

        $this->assertEquals('Testing fault', $fault->getMessage());
    }

    /**
     * __toString() test
     */
    public function testCastsFaultsToString(): void
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $r   = $dom->appendChild($dom->createElement('methodResponse'));
        $f   = $r->appendChild($dom->createElement('fault'));
        $v   = $f->appendChild($dom->createElement('value'));
        $s   = $v->appendChild($dom->createElement('struct'));

        $m1 = $s->appendChild($dom->createElement('member'));
        $m1->appendChild($dom->createElement('name', 'faultCode'));
        $cv = $m1->appendChild($dom->createElement('value'));
        $cv->appendChild($dom->createElement('int', '411'));

        $m2 = $s->appendChild($dom->createElement('member'));
        $m2->appendChild($dom->createElement('name', 'faultString'));
        $sv = $m2->appendChild($dom->createElement('value'));
        $sv->appendChild($dom->createElement('string', 'Testing fault'));

        $xml = $dom->saveXML();

        $e     = new Server\Exception\RuntimeException('Testing fault', 411);
        $fault = Server\Fault::getInstance($e);
        $fault->setEncoding('UTF-8');

        $this->assertEquals(trim($xml), trim($fault->__toString()));
    }
}
