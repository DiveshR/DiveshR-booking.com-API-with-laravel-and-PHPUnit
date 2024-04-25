<?php

namespace App\Http\Controllers\Api\v1\Owner;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Http\Requests\StorePropertyRequest;

class PropertyController extends Controller
{
    

    public function store(StorePropertyRequest $request)
    {
        $this->authorize('properties-manage');
 
        return Property::create($request->validated());
    }
}
