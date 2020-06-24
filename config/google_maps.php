<?php
    return [
        'api_token' => 'GOOGLE_MAPS_API_TOKEN',
        'api_endpoint' => 'https://maps.googleapis.com/maps/api/',
        'save_predictions' => true,
        'countries' => [
            'model' => 'COUNTRIES_MODEL',
            'fields' => [
                'google_id' => '',
                'name' => '',
                'slug' => ''
            ]
        ],
        'regions' => [
            'model' => 'REGIONS_MODEL',
            'fields' => [
                'google_id' => '',
                'name' => '',
                'slug' => ''
            ],
            'relations' => [
                'country_relation_method' => 'country'
            ]
        ],
        'cities' => [
            'model' => 'CITIES_MODEL',
            'fields' => [
                'google_id' => '',
                'name' => '',
                'slug' => ''
            ],
            'relations' => [
                'country_relation_method' => 'country',
                'region_relation_method' => 'region'
            ]
        ],
        'postal_codes' => [
            'model' => 'POST_CODES_MODEL',
            'fields' => [
                'google_id' => '',
                'name' => '',
                'slug' => ''
            ],
            'relations' => [
                'city_relation_method' => 'city',
            ]
        ],
    ];
