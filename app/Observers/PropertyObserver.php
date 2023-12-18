<?php

namespace App\Observers;

use App\Models\Property;
use Spatie\Geocoder\Geocoder;

class PropertyObserver
{
    public function creating(Property $property)
    {
        // We also add the owner automatically
        if (is_null($property->lat) && is_null($property->long) && !(app()->environment('testing'))) {


        if (auth()->check()) {
            $property->owner_id = auth()->id();
        }



        if (is_null($property->lat) && is_null($property->long)) {

            $fullAddress = $property->address_street . ', '
                . $property->address_postcode . ', '
                . $property->city->name . ', '
                . $property->city->country->name;

            $coordinates = Geocoder::getCoordinatesForAddress($fullAddress);

            $property->lat = $coordinates->lat;
            $property->long = $coordinates->lng;
        }
    }
}
}
