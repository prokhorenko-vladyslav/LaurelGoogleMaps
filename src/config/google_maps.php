<?php
    return [
        /**
         * GoogleMaps api token
         */
        'api_token' => 'GOOGLE_MAPS_API_TOKEN',

        /**
         * GoogleMaps api uri
         */
        'api_endpoint' => 'https://maps.googleapis.com/maps/api/',

        /**
         * Need to predictions in database
         */
        'save_predictions' => true,

        'countries' => [
            /**
             * Countries model name
             */
            'model' => 'COUNTRIES_MODEL',

            /**
             * Field names of countries entity
             */
            'fields' => [
                'google_id' => '',
                'name' => '',
                'slug' => ''
            ]
        ],
        'regions' => [
            /**
             * Regions model name
             */
            'model' => 'REGIONS_MODEL',

            /**
             * Field names of regions entity
             */
            'fields' => [
                'google_id' => '',
                'name' => '',
                'slug' => ''
            ],

            /**
             * Relations method for regions entity
             */
            'relations' => [
                'country_relation_method' => 'country'
            ]
        ],
        'cities' => [
            /**
             * Cities model name
             */
            'model' => 'CITIES_MODEL',

            /**
             * Field names of cities entity
             */
            'fields' => [
                'google_id' => '',
                'name' => '',
                'slug' => ''
            ],

            /**
             * Relations method for cities entity
             */
            'relations' => [
                'country_relation_method' => 'country',
                'region_relation_method' => 'region'
            ]
        ],
        'postal_codes' => [
            /**
             * Postal code model name
             */
            'model' => 'POST_CODES_MODEL',

            /**
             * Field names of postal codes entity
             */
            'fields' => [
                'google_id' => '',
                'name' => '',
                'slug' => ''
            ],

            /**
             * Relations method for postal codes entity
             */
            'relations' => [
                'city_relation_method' => 'city',
            ]
        ],
    ];
