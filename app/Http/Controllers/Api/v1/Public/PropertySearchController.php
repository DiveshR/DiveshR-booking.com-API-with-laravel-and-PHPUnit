<?php

namespace App\Http\Controllers\Api\v1\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Property;

class PropertySearchController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        return Property::with('city')
        ->when($request->city, function($query) use ($request){
            $query->where('city_id',$request->city);
        })
        ->when($request->country, function($query) use ($request){
            $query->whereHas('city', fn($q) => $q->where('country_id', $request->country));
        })->get();
    }
}
