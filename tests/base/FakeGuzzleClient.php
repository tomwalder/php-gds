<?php

class FakeGuzzleClient implements \GuzzleHttp\ClientInterface
{

    private $str_url;

    private $arr_params;

    private $obj_fake_response;

    public function __construct($obj_response = null)
    {
        $this->obj_fake_response = $obj_response;
    }

    /**
     * Pretend to do a POST request
     *
     * @param $str_url
     * @param array $arr_params
     * @return \GuzzleHttp\Psr7\Response
     */
    public function post($str_url, array $arr_params)
    {
        // echo $str_url, '::', print_r($arr_params, true), PHP_EOL;
        $this->str_url = $str_url;
        $this->arr_params = $arr_params;

        $obj_response = new \GuzzleHttp\Psr7\Response();
        return $obj_response->withBody(\GuzzleHttp\Psr7\stream_for(json_encode($this->obj_fake_response)));

    }

    public function getPostedUrl()
    {
        return $this->str_url;
    }

    public function getPostedParams()
    {
        return $this->arr_params;
    }

    /**
     * Send an HTTP request.
     *
     * @param \Psr\Http\Message\RequestInterface $request Request to send
     * @param array $options Request options to apply to the given
     *                                  request and to the transfer.
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function send(\Psr\Http\Message\RequestInterface $request, array $options = [])
    {
        // TODO: Implement send() method.
    }

    /**
     * Asynchronously send an HTTP request.
     *
     * @param \Psr\Http\Message\RequestInterface $request Request to send
     * @param array $options Request options to apply to the given
     *                                  request and to the transfer.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function sendAsync(\Psr\Http\Message\RequestInterface $request, array $options = [])
    {
        // TODO: Implement sendAsync() method.
    }

    /**
     * Create and send an HTTP request.
     *
     * Use an absolute path to override the base path of the client, or a
     * relative path to append to the base path of the client. The URL can
     * contain the query string as well.
     *
     * @param string $method HTTP method.
     * @param string|\Psr\Http\Message\UriInterface $uri URI object or string.
     * @param array $options Request options to apply.
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function request($method, $uri, array $options = [])
    {
        // TODO: Implement request() method.
    }

    /**
     * Create and send an asynchronous HTTP request.
     *
     * Use an absolute path to override the base path of the client, or a
     * relative path to append to the base path of the client. The URL can
     * contain the query string as well. Use an array to provide a URL
     * template and additional variables to use in the URL template expansion.
     *
     * @param string $method HTTP method
     * @param string|\Psr\Http\Message\UriInterface $uri URI object or string.
     * @param array $options Request options to apply.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function requestAsync($method, $uri, array $options = [])
    {
        // TODO: Implement requestAsync() method.
    }

    /**
     * Get a client configuration option.
     *
     * These options include default request options of the client, a "handler"
     * (if utilized by the concrete client), and a "base_uri" if utilized by
     * the concrete client.
     *
     * @param string|null $option The config option to retrieve.
     *
     * @return mixed
     */
    public function getConfig($option = null)
    {
        // TODO: Implement getConfig() method.
    }

}