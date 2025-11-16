<?php
namespace App\Exception;

use Exception;

class IpBlackListedException extends Exception
{
    public function __construct(string $ip)
    {
        parent::__construct("IP address '$ip' is blacklisted.", 403);
    }
}