<?php

namespace Laurel\GoogleMaps;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Laurel\GoogleMaps\App\Traits\Remotable;
use Mockery\Exception;

class GoogleMaps
{
    use Remotable;

    protected static $instance;

    protected $savePredictions;

    protected $countryModelClass;
    protected $regionModelClass;
    protected $cityModelClass;
    protected $postalCodeModelClass;

    protected $cityModel;
    protected $regionModel;
    protected $countryModel;
    protected $postalCodeModel;

    protected function __construct()
    {
        $this->savePredictions = (bool)config('laurel.google_maps.save_predictions');
        $this->setCountryModelClass(config('laurel.google_maps.countries.model'));
        $this->setRegionModelClass(config('laurel.google_maps.regions.model'));
        $this->setCityModelClass(config('laurel.google_maps.cities.model'));
        $this->setPostalCodeModelClass(config('laurel.google_maps.postal_codes.model'));
    }

    public function setCountryModelClass($className)
    {
        if (!class_exists($className)) {
            throw new Exception('Country model has not been specified');
        }
        $this->countryModelClass = $className;
    }

    public function getCountryModelClass()
    {
        return $this->countryModelClass;
    }

    public function setRegionModelClass($className)
    {
        if (!class_exists($className)) {
            throw new Exception('Region model has not been specified');
        }
        $this->regionModelClass = $className;
    }

    public function getRegionModelClass()
    {
        return $this->regionModelClass;
    }

    public function setCityModelClass($className)
    {
        if (!class_exists($className)) {
            throw new Exception('City model has not been specified');
        }
        $this->cityModelClass = $className;
    }

    public function getCityModelClass()
    {
        return $this->cityModelClass;
    }

    public function setPostalCodeModelClass($className)
    {
        if (!class_exists($className)) {
            throw new Exception('Postal code model has not been specified');
        }
        $this->postalCodeModelClass = $className;
    }

    public function getPostalCodeModelClass()
    {
        return $this->postalCodeModelClass;
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

    public function savePredictions()
    {
        $this->savePredictions = true;
    }

    public function dontSavePredictions()
    {
        $this->savePredictions = false;
    }

    public function doPredictionsSave()
    {
        return $this->savePredictions;
    }

    public function autocomplete(string $searchQuery)
    {
        $response = $this->fetchPredictions($searchQuery);

        if (!empty($response['predictions']) && !empty($response['status']) && $response['status'] === "OK") {
            if ($this->doPredictionsSave()) {
                $this->savePredictionsInDatabase($response['predictions']);
            }
            return $response['predictions'];
        } else {
            return [];
        }
    }

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

    protected function getAutocompleteRoute()
    {
         return config('laurel.google_maps.api_endpoint') . "place/autocomplete/json";
    }

    protected function getLocale()
    {
        try {
            return !empty(App::getLocale()) ? App::getLocale() : config('app.fallback_locale');
        } catch (\Exception $e) {
            return config('app.fallback_locale');
        }
    }

    protected function savePredictionsInDatabase(array $predictions)
    {
        foreach ($predictions as $index => $prediction) {
            $this->savePredictionByType($prediction);
            $this->clearModels();
        }
    }

    protected function clearModels()
    {
        $this->cityModel = null;
        $this->regionModel = null;
        $this->countryModel = null;
        $this->postalCodeModel = null;
    }

    protected function savePredictionByType(array $prediction)
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

    protected function buildCityModel(array $prediction)
    {
        $cityClass = $this->getCityModelClass();
        return $cityClass::firstOrNew([
            config('laurel.google_maps.cities.fields.google_id') => $prediction['id']
        ],[
            config('laurel.google_maps.cities.fields.name') => $prediction['structured_formatting']['main_text'],
            config('laurel.google_maps.cities.fields.slug') => Str::slug($prediction['structured_formatting']['main_text']),
            config('laurel.google_maps.cities.fields.google_id') => $prediction['id']
        ]);
    }

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

    protected function saveModels()
    {
        $this->saveCountryModel();
        $this->saveRegionModel();
        $this->saveCityModel();
        $this->savePostalCodeModel();
    }

    protected function saveCountryModel()
    {
        if (!empty($this->countryModel)) {
            $this->countryModel->saveOrFail();
        }
    }

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
