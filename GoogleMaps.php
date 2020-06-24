<?php

namespace Laurel\GoogleMaps;


use Laurel\GoogleMaps\App\Traits\Remotable;

class GoogleMaps
{
    use Remotable;

    protected static $instance;

    protected function __construct()
    {
    }

    protected function __clone()
    {
    }

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function autocomplete(string $searchQuery)
    {
        $route = config('laurel.google_maps.api_endpoint');
        $parameters = [
            'input' => $searchQuery,
            'types' => '(regions)',
            'language' => config('laurel.google_maps.locale'),
            'key' => config('laurel.google_maps.api_token')
        ];

        $response = $this->sendRequest($route, $parameters);
        if (!empty($response['predictions']) && !empty($response['status']) && $response['status'] === "OK") {
            return $response['predictions'];
        } else {
            return [];
        }
    }
}
