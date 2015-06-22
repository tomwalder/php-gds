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
    protected $str_request_body_for_testing = NULL;

    /**
     * @var string
     */
    protected $str_request_url_for_testing = NULL;

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
        return array(json_encode((object)['test' => true]), [], '200');
    }

//    /**
//     * Get the client built request body
//     *
//     * @return string|null
//     */
//    public function getRequestBody()
//    {
//        return $this->str_request_body_for_testing;
//    }
//
//    /**
//     * Get the client built request URL
//     *
//     * @return string|null
//     */
//    public function getRequestUrl()
//    {
//        return $this->str_request_url_for_testing;
//    }

    public function expectRequest($str_url, $str_req)
    {
        $this->str_request_url_for_testing = $str_url;
        $this->str_request_body_for_testing = $str_req;
    }


}