<?php

namespace Laminas\XmlRpc\Client;

use Laminas\XmlRpc\Client as XMLRPCClient;

use function ltrim;

/**
 * The namespace decorator enables object chaining to permit
 * calling XML-RPC namespaced functions like "foo.bar.baz()"
 * as "$remote->foo->bar->baz()".
 */
class ServerProxy
{
    /** @var array of \Laminas\XmlRpc\Client\ServerProxy */
    private array $cache = [];

    public function __construct(private readonly XMLRPCClient $client, private readonly string $namespace = '')
    {
    }

    /**
     * Get the next successive namespace
     *
     * @param string $namespace
     * @return ServerProxy
     */
    public function __get($namespace)
    {
        $namespace = ltrim("$this->namespace.$namespace", '.');
        if (! isset($this->cache[$namespace])) {
            $this->cache[$namespace] = new $this($this->client, $namespace);
        }
        return $this->cache[$namespace];
    }

    /**
     * Call a method in this namespace.
     *
     * @param  string $method
     * @param  array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        $method = ltrim("{$this->namespace}.{$method}", '.');
        return $this->client->call($method, $args);
    }
}
