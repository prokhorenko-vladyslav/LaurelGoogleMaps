This package provides tools that allow add to your application location autocomplete. Search works using GoogleMaps Places API.

# Installing with Composer
You can install this package via Composer with this command
> composer require laurel/google-maps

# Installation in Laravel
To install in Laravel you need to modify the `providers` array in `config/app.php` to include the service provider
> 'providers' => [
>
>       //..
>       Laurel\GoogleMaps\App\Providers\GoogleMapsServiceProvider::class,
>
> ],

Then run `composer update`.

After that you need to publish config files. To do this run next command:
> php artisan vendor:publish --tag=config --provider=Laurel\GoogleMaps\App\Providers\GoogleMapsServiceProvider

Specify in the package config file models, fields and relation method for Countries, Regions, Cities and PostalCodes.

# Using
To get GoogleMaps API predictions use next method:
> \Laurel\GoogleMaps\GoogleMaps::instance()->autocomplete(string $searchQuery);
