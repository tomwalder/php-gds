<?php

/**
 * Class DenyGQLProxyMock
 *
 * @author Tom Walder <tom@docnet.nu>
 *
 */
class DenyGQLProxyMock extends google\appengine\testing\ApiProxyMock
{

    /**
     * Make Sync Call
     *
     * @param $package
     * @param $call_name
     * @param $req
     * @param $resp
     * @param null $deadline
     * @throws Exception
     * @throws \google\appengine\runtime\ApplicationError
     */
    public function makeSyncCall($package, $call_name, $req, $resp, $deadline = null) {
        if($req instanceof \google\appengine\datastore\v4\RunQueryRequest && $req->hasGqlQuery()) {
            throw new \google\appengine\runtime\ApplicationError('application error', 'GQL not supported.');
        }
        parent::makeSyncCall($package, $call_name, $req, $resp, $deadline);
    }

}