<?php


namespace Laurel\GoogleMaps\App\Traits;


use Illuminate\Support\Facades\Http;

trait Remotable
{
    public function sendRequest($uri, $data, $method = 'get')
    {
        return Http::$method($uri, $data)->json();
    }
}
