<?php

declare(strict_types=1);

namespace LaminasTest\XmlRpc\Request;

use Laminas\XmlRpc\Request;
use LaminasTest\XmlRpc\PhpInputMock;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function str_starts_with;
use function strlen;

#[Group('Laminas_XmlRpc')]
class HttpTest extends TestCase
{
    private string $xml;
    private Request\Http $request;
    private array $server;

    /**
     * Setup environment
     */
    protected function setUp(): void
    {
        $this->xml     = <<<EOX
<?xml version="1.0" encoding="UTF-8"?>
<methodCall>
    <methodName>test.userUpdate</methodName>
    <params>
        <param>
            <value><string>blahblahblah</string></value>
        </param>
        <param>
            <value><struct>
                <member>
                    <name>salutation</name>
                    <value><string>Felsenblöcke</string></value>
                </member>
                <member>
                    <name>firstname</name>
                    <value><string>Lépiné</string></value>
                </member>
                <member>
                    <name>lastname</name>
                    <value><string>Géranté</string></value>
                </member>
                <member>
                    <name>company</name>
                    <value><string>Laminas Technologies, Inc.</string></value>
                </member>
            </struct></value>
        </param>
    </params>
</methodCall>
EOX;
        $this->request = new Request\Http();
        $this->request->loadXml($this->xml);

        $this->server = $_SERVER;
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                unset($_SERVER[$key]);
            }
        }
        $_SERVER['HTTP_USER_AGENT']     = 'Laminas_XmlRpc_Client';
        $_SERVER['HTTP_HOST']           = 'localhost';
        $_SERVER['HTTP_CONTENT_TYPE']   = 'text/xml';
        $_SERVER['HTTP_CONTENT_LENGTH'] = strlen($this->xml) + 1;
        PhpInputMock::mockInput($this->xml);
    }

    /**
     * Teardown environment
     */
    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        unset($this->request);
        PhpInputMock::restoreDefault();
    }

    public function testGetRawRequest(): void
    {
        $this->assertEquals($this->xml, $this->request->getRawRequest());
    }

    public function testGetHeaders(): void
    {
        $expected = [
            'User-Agent'     => 'Laminas_XmlRpc_Client',
            'Host'           => 'localhost',
            'Content-Type'   => 'text/xml',
            'Content-Length' => 961,
        ];
        $this->assertEquals($expected, $this->request->getHeaders());
    }

    public function testGetFullRequest(): void
    {
        $expected  = <<<EOT
User-Agent: Laminas_XmlRpc_Client
Host: localhost
Content-Type: text/xml
Content-Length: 961

EOT;
        $expected .= $this->xml;

        $this->assertEquals($expected, $this->request->getFullRequest());
    }

    public function testExtendingClassShouldBeAbleToReceiveMethodAndParams(): void
    {
        $request = new TestAsset\HTTPTestExtension('foo', ['bar', 'baz']);
        $this->assertEquals('foo', $request->getMethod());
        $this->assertEquals(['bar', 'baz'], $request->getParams());
    }

    public function testHttpRequestReadsFromPhpInput(): void
    {
        $this->assertNull(PhpInputMock::argumentsPassedTo('stream_open'));
        $request       = new Request\Http();
        [$path, $mode] = PhpInputMock::argumentsPassedTo('stream_open');
        $this->assertSame('php://input', $path);
        $this->assertSame('rb', $mode);
        $this->assertSame($this->xml, $request->getRawRequest());
    }

    public function testHttpRequestGeneratesFaultIfReadFromPhpInputFails(): void
    {
        PhpInputMock::methodWillReturn('stream_open', false);
        $request = new Request\Http();
        $this->assertTrue($request->isFault());
        $this->assertSame(630, $request->getFault()->getCode());
    }
}
