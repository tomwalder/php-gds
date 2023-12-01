<?php

class RESTv1GatewayBackoff extends \GDS\Gateway\RESTv1
{

    public function runExecuteWithExponentialBackoff(
        callable $fnc_main,
        string $str_exception = null,
        callable $fnc_resolve_exception = null
    ) {
        return $this->executeWithExponentialBackoff($fnc_main, $str_exception, $fnc_resolve_exception);
    }
}
