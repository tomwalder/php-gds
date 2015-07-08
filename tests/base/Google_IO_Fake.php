<?php

/**
 * Implementation of IO class for testing
 *
 * @author Tom Walder <tom@docnet.nu>
 */
class Google_IO_Fake extends Google_IO_Stream
{

    /**
     * @var string
     */
    protected $str_request_body_for_testing = null;

    /**
     * @var string
     */
    protected $str_request_url_for_testing = null;

    /**
     * @var string
     */
    protected $str_expected_response = null;

    /**
     * Replace the execute method, so we can test against the contents
     *
     * Don't actually do anything, just return a faked up response
     *
     * @param Google_Http_Request $request the http request to be executed
     * @return array containing response headers, body, and http code
     */
    public function executeRequest(Google_Http_Request $request)
    {
        PHPUnit_Framework_Assert::assertEquals($this->str_request_body_for_testing, $request->getPostBody());
        PHPUnit_Framework_Assert::assertEquals($this->str_request_url_for_testing, $request->getUrl());
        return array($this->str_expected_response, [], '200');
    }

    /**
     * Set up the expected request and response strings
     *
     * @param $str_url
     * @param $str_req
     * @param $str_response
     */
    public function expectRequest($str_url, $str_req, $str_response)
    {
        $this->str_request_url_for_testing = $str_url;
        $this->str_request_body_for_testing = $str_req;
        $this->str_expected_response = $str_response;
    }


}