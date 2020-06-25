<?php

namespace Laurel\GoogleMaps;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Laurel\GoogleMaps\App\Traits\Remotable;
use Mockery\Exception;

/**
 * Class for fetching and saving predictions from GoogleMaps API
 *
 * Class GoogleMaps
 * @package Laurel\GoogleMaps
 */
class GoogleMaps
{
    use Remotable;

    /**
     * Instance of package entity
     *
     * @var
     */
    protected static $instance;

    /**
     * Need to save predictions or not
     *
     * @var bool
     */
    protected $savePredictions;

    /**
     * Class name of a country model
     *
     * @var
     */
    protected $countryModelClass;

    /**
     * Class name of a region model
     *
     * @var
     */
    protected $regionModelClass;

    /**
     * Class name of a city model
     *
     * @var
     */
    protected $cityModelClass;

    /**
     * Class name of a postal code model
     *
     * @var
     */
    protected $postalCodeModelClass;

    /**
     * City model
     *
     * @var
     */
    protected $cityModel;

    /**
     * Region model
     *
     * @var
     */
    protected $regionModel;

    /**
     * Country model
     *
     * @var
     */
    protected $countryModel;

    /**
     * Model of a postal code
     *
     * @var
     */
    protected $postalCodeModel;

    /**
     * GoogleMaps constructor.
     */
    protected function __construct()
    {
        $this->savePredictions = (bool)config('laurel.google_maps.save_predictions');
        $this->setCountryModelClass(config('laurel.google_maps.countries.model'));
        $this->setRegionModelClass(config('laurel.google_maps.regions.model'));
        $this->setCityModelClass(config('laurel.google_maps.cities.model'));
        $this->setPostalCodeModelClass(config('laurel.google_maps.postal_codes.model'));
    }

    /**
     * Setter for class name of a country model
     *
     * @param $className
     */
    public function setCountryModelClass($className)
    {
        if (!class_exists($className)) {
            throw new Exception('Country model has not been specified');
        }
        $this->countryModelClass = $className;
    }

    /**
     * Getter for class name of a country model
     *
     * @return mixed
     */
    public function getCountryModelClass()
    {
        return $this->countryModelClass;
    }

    /**
     * Setter for class name of a region model
     *
     * @param $className
     */
    public function setRegionModelClass($className)
    {
        if (!class_exists($className)) {
            throw new Exception('Region model has not been specified');
        }
        $this->regionModelClass = $className;
    }

    /**
     * Getter for class name of a region model
     *
     * @return mixed
     */
    public function getRegionModelClass()
    {
        return $this->regionModelClass;
    }

    /**
     * Setter for class name of a city model
     *
     * @param $className
     */
    public function setCityModelClass($className)
    {
        if (!class_exists($className)) {
            throw new Exception('City model has not been specified');
        }
        $this->cityModelClass = $className;
    }

    /**
     * Getter for class name of a city model
     *
     * @return mixed
     */
    public function getCityModelClass()
    {
        return $this->cityModelClass;
    }

    /**
     * Setter for class name of a postal code model
     *
     * @param $className
     */
    public function setPostalCodeModelClass($className)
    {
        if (!class_exists($className)) {
            throw new Exception('Postal code model has not been specified');
        }
        $this->postalCodeModelClass = $className;
    }

    /**
     * Getter for class name of a postal code model
     *
     * @return mixed
     */
    public function getPostalCodeModelClass()
    {
        return $this->postalCodeModelClass;
    }

    /**
     * Clone magic method
     */
    protected function __clone()
    {
    }

    /**
     * Returns entity instance
     *
     * @return GoogleMaps
     */
    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Activates prediction saving
     */
    public function savePredictions()
    {
        $this->savePredictions = true;
    }

    /**
     * Deactivates prediction saving
     */
    public function dontSavePredictions()
    {
        $this->savePredictions = false;
    }

    /**
     * Returns true, if predictions saving is activated
     *
     * @return bool
     */
    public function doPredictionsSave()
    {
        return $this->savePredictions;
    }

    /**
     * Method for searching cities, regions, countries and postal codes in GoogleMaps
     *
     * @param string $searchQuery
     * @return array|mixed
     */
    public function autocomplete(string $searchQuery)
    {
        $response = $this->fetchPredictions($searchQuery);

        if (!empty($response['predictions']) && !empty($response['status']) && $response['status'] === "OK") {
            if ($this->doPredictionsSave()) {
                return $this->savePredictionsInDatabase($response['predictions']);
            }
            return $response['predictions'];
        } else {
            return [];
        }
    }

    /**
     * Fetches predictions from GoogleMaps API
     *
     * @param string $searchQuery
     * @return mixed
     */
    public function fetchPredictions(string $searchQuery)
    {
        $route = $this->getAutocompleteRoute();
        $parameters = [
            'input' => $searchQuery,
            'types' => '(regions)',
            'language' => $this->getLocale(),
            'key' => config('laurel.google_maps.api_token')
        ];

        return $this->sendRequest($route, $parameters);
    }

    /**
     * Returns autocomplete route
     *
     * @return string
     */
    protected function getAutocompleteRoute()
    {
         return config('laurel.google_maps.api_endpoint') . "place/autocomplete/json";
    }

    /**
     * Returns application locale
     *
     * @return string
     */
    protected function getLocale()
    {
        try {
            return !empty(App::getLocale()) ? App::getLocale() : config('app.fallback_locale');
        } catch (\Exception $e) {
            return config('app.fallback_locale');
        }
    }

    /**
     * Calls methods for saving predictions in database
     *
     * @param array $predictions
     * @return array
     */
    protected function savePredictionsInDatabase(array $predictions)
    {
        $predictionModels = [];
        foreach ($predictions as $index => $prediction) {
            $this->saveSinglePrediction($prediction);

            $predictionModels[] = [
                'city' => $this->cityModel,
                'region' => $this->regionModel,
                'country' => $this->countryModel,
                'postal_code' => $this->postalCodeModel,
            ];

            $this->clearModels();
        }

        return $predictionModels;
    }

    /**
     * Clears filled models
     */
    protected function clearModels()
    {
        $this->cityModel = null;
        $this->regionModel = null;
        $this->countryModel = null;
        $this->postalCodeModel = null;
    }

    /**
     * Saves single prediction and their terms
     *
     * @param array $prediction
     */
    protected function saveSinglePrediction(array $prediction)
    {
        if (empty($prediction['types'])) {
            return;
        }

        /**
         * Fix for postal codes
         */
        if (in_array('postal_code', $prediction['types'])) {
            $prediction['terms'] = array_reverse($prediction['terms']);
        }

        $this->processPrediction($prediction);
        $this->processTerms($prediction);
        $this->saveModels();
    }

    /**
     * Detects prediction type and builds model for each of this
     *
     * @param array $prediction
     */
    protected function processPrediction(array $prediction)
    {
        if (in_array('locality', $prediction['types']) && empty($this->cityModel)) {
            $this->cityModel = $this->buildCityModel($prediction);
        } else if (in_array('country', $prediction['types']) && empty($this->countryModel)) {
            $this->countryModel = $this->buildCountryModel($prediction);
        } else if (in_array('administrative_area_level_1', $prediction['types']) && empty($this->regionModel)) {
            $this->regionModel = $this->buildRegionModel($prediction);
        } else if (in_array('postal_code', $prediction['types']) && empty($this->postalCodeModel)) {
            $this->postalCodeModel = $this->buildPostalCodeModel($prediction);
        }
    }

    /**
     * Saves prediction terms
     *
     * @param array $prediction
     */
    protected function processTerms(array $prediction)
    {
        if (!empty($prediction['terms']) && is_array($prediction['terms'])) {
            foreach ($prediction['terms'] as $index => $term) {
                $response = $this->fetchPredictions($term['value']);
                if (empty($response['status']) || $response['status'] !== 'OK') {
                    return;
                }

                foreach ($response['predictions'] as $prediction) {
                    if (!empty($response['predictions'])) {
                        $this->processPrediction($prediction);
                    }
                }
            }
        }
    }

    /**
     * Creates city model using prediction
     *
     * @param array $prediction
     * @return mixed
     */
    protected function buildCityModel(array $prediction)
    {
        $cityClass = $this->getCityModelClass();
        return $cityClass::firstOrNew([
            config('laurel.google_maps.cities.fields.google_id') => $prediction['id']
        ], [
            config('laurel.google_maps.cities.fields.name') => $prediction['structured_formatting']['main_text'],
            config('laurel.google_maps.cities.fields.slug') => Str::slug($prediction['structured_formatting']['main_text']),
            config('laurel.google_maps.cities.fields.google_id') => $prediction['id']
        ]);
    }

    /**
     * Creates country model using prediction
     *
     * @param array $prediction
     * @return mixed
     */
    protected function buildCountryModel(array $prediction)
    {
        $countryClass = $this->getCountryModelClass();
        return $countryClass::firstOrNew([
            config('laurel.google_maps.countries.fields.google_id') => $prediction['id']
        ], [
            config('laurel.google_maps.countries.fields.name') => $prediction['structured_formatting']['main_text'],
            config('laurel.google_maps.countries.fields.slug') => Str::slug($prediction['structured_formatting']['main_text']),
            config('laurel.google_maps.countries.fields.google_id') => $prediction['id']
        ]);
    }

    /**
     * Creates region model using prediction
     *
     * @param array $prediction
     * @return mixed
     */
    protected function buildRegionModel(array $prediction)
    {
        $regionClass = $this->getRegionModelClass();
        return $regionClass::firstOrNew([
            config('laurel.google_maps.regions.fields.google_id') => $prediction['id']
        ], [
            config('laurel.google_maps.regions.fields.name') => $prediction['structured_formatting']['main_text'],
            config('laurel.google_maps.regions.fields.slug') => Str::slug($prediction['structured_formatting']['main_text']),
            config('laurel.google_maps.regions.fields.google_id') => $prediction['id']
        ]);
    }

    /**
     * Creates postal code model using prediction
     *
     * @param array $prediction
     * @return mixed
     */
    protected function buildPostalCodeModel(array $prediction)
    {
        $postalCodeClass = $this->getPostalCodeModelClass();
        return $postalCodeClass::firstOrNew([
            config('laurel.google_maps.postal_codes.fields.google_id') => $prediction['id']
        ], [
            config('laurel.google_maps.postal_codes.fields.name') => $prediction['structured_formatting']['main_text'],
            config('laurel.google_maps.postal_codes.fields.slug') => Str::slug($prediction['structured_formatting']['main_text']),
            config('laurel.google_maps.postal_codes.fields.google_id') => $prediction['id']
        ]);
    }

    /**
     * Calls methods for saving models
     */
    protected function saveModels()
    {
        $this->saveCountryModel();
        $this->saveRegionModel();
        $this->saveCityModel();
        $this->savePostalCodeModel();
    }

    /**
     * Saves country model, if it exists
     */
    protected function saveCountryModel()
    {
        if (!empty($this->countryModel)) {
            $this->countryModel->saveOrFail();
        }
    }

    /**
     * Saves region model, if it exists
     */
    protected function saveRegionModel()
    {
        if (!empty($this->regionModel)) {
            $countryRelationMethod = config('laurel.google_maps.regions.relations.country_relation_method');
            if (!empty($countryRelationMethod) && is_string($countryRelationMethod) && method_exists($this->regionModel, $countryRelationMethod)) {
                $this->regionModel->$countryRelationMethod()->associate($this->countryModel);
            }

            $this->regionModel->saveOrFail();
        }
    }

    /**
     * Saves city model, if it exists
     */
    protected function saveCityModel()
    {
        if (!empty($this->cityModel)) {
            $countryRelationMethod = config('laurel.google_maps.cities.relations.country_relation_method');
            if (!empty($countryRelationMethod) && is_string($countryRelationMethod) && method_exists($this->cityModel, $countryRelationMethod)) {
                $this->cityModel->$countryRelationMethod()->associate($this->countryModel);
            }

            $regionsRelationMethod = config('laurel.google_maps.cities.relations.region_relation_method');
            if (!empty($regionsRelationMethod) && is_string($regionsRelationMethod) && method_exists($this->cityModel, $regionsRelationMethod)) {
                $this->cityModel->$regionsRelationMethod()->associate($this->regionModel);
            }

            $this->cityModel->saveOrFail();
        }
    }

    /**
     * Saves postal code model, if it exists
     */
    protected function savePostalCodeModel()
    {
        if (!empty($this->postalCodeModel)) {
            $cityRelationMethod = config('laurel.google_maps.postal_codes.relations.city_relation_method');
            if (!empty($cityRelationMethod) && is_string($cityRelationMethod) && method_exists($this->postalCodeModel, $cityRelationMethod)) {
                $this->postalCodeModel->$cityRelationMethod()->associate($this->cityModel);
            }

            $this->postalCodeModel->saveOrFail();
        }
    }
}
