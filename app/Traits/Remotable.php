<?php


namespace Laurel\GoogleMaps\App\Traits;


use Illuminate\Support\Facades\Http;

/**
 * Trait for sending requests
 *
 * Trait Remotable
 * @package Laurel\GoogleMaps\App\Traits
 */
trait Remotable
{
    /**
     * Sends request to specified uri
     *
     * @param $uri
     * @param $data
     * @param string $method
     * @return mixed
     */
    public function sendRequest($uri, $data, $method = 'get')
    {
        return Http::$method($uri, $data)->json();
    }
}
