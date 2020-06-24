<?php
    return [
        'api_token' => 'GOOGLE_MAPS_API_TOKEN',
        'api_endpoint' => 'https://maps.googleapis.com/maps/api/',
        'locale' => \Illuminate\Support\Facades\App::getLocale(),
        'countries' => [
            'table_name' => 'NAME_OF_THE_TABLE_WITH_COUNTRIES',
            'fields' => [
                'google_id' => '',
                'name' => '',
                'slug' => ''
            ]
        ],
        'regions' => [
            'table_name' => 'NAME_OF_THE_TABLE_WITH_REGIONS',
            'fields' => [
                'google_id' => '',
                'name' => '',
                'slug' => ''
            ]
        ],
        'cities' => [
            'table_name' => 'NAME_OF_THE_TABLE_WITH_CITIES',
            'fields' => [
                'google_id' => '',
                'name' => '',
                'slug' => ''
            ]
        ],
        'postal_codes' => [
            'table_name' => 'NAME_OF_THE_TABLE_WITH_POSTAL_CODES',
            'fields' => [
                'google_id' => '',
                'name' => '',
                'slug' => ''
            ]
        ],
    ];
