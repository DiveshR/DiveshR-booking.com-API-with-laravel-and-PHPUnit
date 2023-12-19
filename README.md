

## Booking.com with Laravel

Re Creating Booking.com API with Laravel and PHPUnit.

- We will create a DB structure and API endpoints for managing properties, property search, showing apartments/rooms data, and making bookings.

- All of that is covered by automated tests with PHPUnit.


# Profile Fields Structure

## Goals of This Lesson

* Think about different ways of structuring profiles in DB
* Add the necessary fields
* Look at a different project scenario: doctors/patients

# Users Table or Profile Table?

Common fields for both users/owners:

* Full name
* Display name
* Phone number
* Email and phone confirmation
* Photo
* Invoices: country, city, postcode, address

Simple users have these extra "individual" fields:

* Gender
* Nationality
* Date of birth

Then, property owners don't have to specify gender/nationality/birth date, but they have only one "special" extra field: description about themselves.

Yeah, it's pretty simple. Property owners have only one personal field, everything else is related to their properties.

So, all the common fields should probably be in the users DB table, just some of them nullable?

First, we clearly need the migration for the countries table:

```php
php artisan make:model Country -m
```

```php
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }
````

Common fields for both users/owners:

 - Full name
 - Display name
 - Phone number
 - Email and phone confirmation
 - Photo
 - Invoices: country, city, postcode, address

Simple users have these extra "individual" fields:

 - Gender
 - Nationality
 - Date of birth

```php
php artisan make:migration add_additional_fields_to_users_table
````

```php
        Schema::table('users', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('id');
            $table->string('phone_number')->nullable()->after('email_verified_at');
            $table->string('photo')->after('phone_number')->nullable();
            $table->timestamp('phone_verified_at')->nullable()->after('photo');
        });
````

```php
php artisan make:model UserProfile -m
```

```php
    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }
```

Migration for User Profiles:

```php
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('invoice_address')->nullable();
            $table->string('invoice_postcode')->nullable();
            $table->string('invoice_city')->nullable();
            $table->foreignId('invoice_country_id')->nullable()->constrained('countries');
            $table->string('gender')->nullable();
            $table->date('birth_date')->nullable();
            $table->foreignId('nationality_country_id')->nullable()->constrained('countries');
            $table->text('description')->nullable();
            $table->timestamps();
        });
`````

Now, the User::all() in the code would return only the main User fields, and if someone wants to know the invoice details, they would do User::with('profile')->get().
 
-----------------------------------------------------------------------------

# Properties and Apartments


##  Countries, Cities, Geographical Objects


 The next thing we'll work on is adding real estate properties: houses/homes to rent. In this particular lesson, we will focus on adding the geographical data for city, country, and latitude/longitude.

### Goals of This Lesson

- Build a DB schema for countries, cities, and geographical objects, seeding a few of each
- Build a first version of DB schema for properties, with geographical data
- Automatically set property latitude/longitude based on the address, with Observer and Google Maps API
- First version of API endpoint to create a property, covered by PHPUnit test

First, let's add the latitude and longitude columns to the DB table of countries.

```php
php artisan make:migration add_geocoordinates_to_countries_table
```

```php
        Schema::table('countries', function (Blueprint $table) {
            $table->after('name', function () use ($table) {
                $table->decimal('lat', 10, 7)->nullable();
                $table->decimal('long', 10, 7)->nullable();
            });
        });
````

Next, we will definitely build a search by city, so we need a model for that as well, with coordinates.

```php
php artisan make:model City -ms
```

```php
class City extends Model
{
    use HasFactory;

    protected $fillable = ['country_id', 'name', 'lat', 'long'];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}

````
```php
Schema::create('cities', function (Blueprint $table) {
    $table->id();
    $table->foreignId('country_id')->constrained();
    $table->string('name');
    $table->decimal('lat', 10, 7)->nullable();
    $table->decimal('long', 10, 7)->nullable();
    $table->timestamps();
});
```

Finally, let's create a separate database table for geographical locations, such as "Taj Mahal" or "India Gate", cause people often search by them.


Search by geolocation

```php
php artisan make:model Geoobject -ms
````

app/Models/Geoobject.php:

```php
class Geoobject extends Model
{
    use HasFactory;
 
    protected $fillable = ['city_id', 'name', 'lat', 'long'];
}
```

```php
        Schema::create('geoobjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->nullable()->constrained();
            $table->string('name');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('long', 10, 7)->nullable();
            $table->timestamps();
        });
````

Next, let's build the Seeders for all those new tables. We will use them to automatically run seeds in our automated tests, too. So, we fill in a few countries, a few cities, and a few geographical objects.

```php
php artisan make:seeder CitySeeder
php artisan make:seeder GeoobjectSeeder
php artisan make:seeder CountrySeeder
`````

database/seeders/CountrySeeder.php:

```php
    public function run(): void
    {
        Country::create([
            'name' => 'India',
            'lat' => 20.5937,
            'long' => 78.9629
        ]);
        Country::create([
            'name' => 'China',
            'lat' => 35.8617,
            'long' => 104.1954
        ]);
    }
```

database/seeders/CitySeeder.php:

```php
    public function run(): void
    {
        City::create([
            'country_id' => 1,
            'name' => 'Dehradun',
            'lat' => 30.3165,
            'long' => 78.0322,
        ]);

        City::create([
            'country_id' => 2,
            'name' => 'Beijing',
            'lat' => 39.9042,
            'long' => 116.4074,
        ]);
    }
```

database/seeders/GeoobjectSeeder.php:

```php
    public function run(): void
    {
        Geoobject::create([
            'city_id' => 1,
            'name' => 'Indian Military Academy',
            'lat' => 30.3382,
            'long' => 77.9922
        ]);

        Geoobject::create([
            'city_id' => 2,
            'name' => 'Baliqiao',
            'lat' => 32.511,
            'long' => 120.833
        ]);
    }

````

Then we, of course, add them all to the main DatabaseSeeder, which now will look like this:

```php
class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(RoleSeeder::class);
        $this->call(AdminUserSeeder::class);
        $this->call(PermissionSeeder::class);
 
        $this->call(CountrySeeder::class);
        $this->call(CitySeeder::class);
        $this->call(GeoobjectSeeder::class);
    }
}
````

Great, now we have some geographical entities to play around with, now let's go to the actual properties!

```php
php artisan make:model Property -ms
```

And here's the schema with the main fields, for now. There will be more, but at the moment, we focus on geographical things for the search, remember?

Migration for properties

```php
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users');
            $table->string('name');
            $table->foreignId('city_id')->constrained();
            $table->string('address_street');
            $table->string('address_postcode')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('long', 10, 7)->nullable();
            $table->timestamps();
        });
```
app/Models/Property.php:

```php

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'city_id',
        'address_street',
        'address_postcode',
        'lat',
        'long',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

```

Now, when someone enters a new property, wouldn't it be nice if lat/long fields would be automatically populated, by street address?

We will use a package called Geocode addresses to coordinates that allows you to easily integrate Google Maps API in your Laravel project.

```php
composer require spatie/geocoder

php artisan vendor:publish --provider="Spatie\Geocoder\GeocoderServiceProvider" --tag="config"

````
Then, we add the Google Maps API key (read here how to get it) to the .env file:

GOOGLE_MAPS_GEOCODING_API_KEY=AIzaSyAWRsRGOFbTXRlLHDOSudkerLjUtBfElUt

To automate all this process, we create an Observer file, to watch for the creation of the new Properties.

```php
php artisan make:observer PropertyObserver --model=Property
````
app/Observers/PropertyObserver.php:

```php
namespace App\Observers;

use App\Models\Property;
use Spatie\Geocoder\Geocoder;

class PropertyObserver
{
    public function creating(Property $property)
    {
        // We also add the owner automatically
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

```

Finally, we register that Observer, let's do it directly in the Model.

app/Models/Property.php:

```php
use App\Observers\PropertyObserver;
 
class Property extends Model
{
    // ...
 
    public static function booted()
    {
        parent::booted();
 
        self::observe(PropertyObserver::class);
    }
}
```

Creating Property: Route/Controller/Request
Now, let's build a Controller/Route to create a new property, and add a Form Request , too.

```php
php artisan make:controller Api/v1/Owner/PropertyController
php artisan make:request StorePropertyRequest
```

app/Http/Controllers/Api/v1/Owner/PropertyController.php:
```php
    public function store(StorePropertyRequest $request)
    {
        $this->authorize('properties-manage');
 
        return Property::create($request->validated());
    }
```
StorePropertyRequest.php

```php

    public function rules(): array
    {
        return [
            'name' => 'required',
            'city_id' => 'required|exists:cities,id',
            'address_street' => 'required',
            'address_postcode' => 'required',
        ];
    }
```

As you can see, we require only the name/city/address, as owner/lat/long will be filled automatically by the Observer.

Finally, the new route:

```php
    Route::post('owner/properties',
        [\App\Http\Controllers\Owner\PropertyController::class, 'store']);
```

Finally, in this lesson, let's add the automatic test that it actually works.

tests/Feature/PropertiesTest.php:

```php artisan make:test PropertiesTest```

PropertiesTest.php
```php
    public function test_property_owner_can_add_property()
    {
        $owner = User::factory()->create(['role_id' => Role::ROLE_OWNER]);
        $response = $this->actingAs($owner)->postJson('/api/owner/properties', [
            'name' => 'My property',
            'city_id' => City::value('id'),
            'address_street' => 'Street Address 1',
            'address_postcode' => '12345',
        ]);
 
        $response->assertSuccessful();
        $response->assertJsonFragment(['name' => 'My property']);
    }
```

A debatable question is whether we should leave the auto-coordinates enabled while testing. Probably not, as we don't want to get charged for Google API every time we run automated tests, right?

So, this is how I disable that part of the Observer:

app/Observers/PropertyObserver.php:

```php


class PropertyObserver
{
    public function creating(Property $property)
    {
        if (is_null($property->lat) && is_null($property->long) && !(app()->environment('testing'))) {
 
            // ... getting the coordinates
 
        }
    }
}
````

-----------------------------------------------------------------------------------------

# Search for Property by City or GeoObject

## Goals of This Lesson

- Create API endpoint (Route + Controller) for searching the properties by city, country, or geographical object
- Write PHPUnit tests for all those cases

# Creating Controller and Route

We will search with these criteria:

- By city
- By country
- Close to a geographical object (by its latitude/longitude)

Let's build the controller and method for this.

```php
php artisan make:controller Api/v1/Public/PropertySearchController  --invokable
```

app/Http/Controllers/Api/v1/Public/PropertySearchController.php:

```php
namespace App\Http\Controllers\Api\v1\Public;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;

class PropertySearchController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        return Property::with('city')
           //
           ->get();
    }
`````

As you can see, we're adding another namespace of /Public, so we'll have three zones: owner, user, and public.

And let's add the route, also grouping the owner/user routes with prefixes.

routes/api.php:

```php
Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('owner')->group(function (){
        Route::get('properties', [PropertyController::class, 'index']);
        Route::post('properties', [PropertyController::class, 'store']);
    });

    Route::prefix('user')->group(function (){
        Route::get('bookings', [BookingController::class, 'index']);
    });
 
});

Route::get('search', PropertySearchController::class);

``````


Our search should be public for everyone, without any registration, so we put that route outside of the auth:sanctum Middleware group.

Now, let's start filling in various search cases. For that, we will use the Eloquent syntax of Model::when() with different conditions.

### Search by City

```php
class PropertySearchController extends Controller
{
    public function __invoke(Request $request)
    {
        return Property::with('city')
            ->when($request->city, function($query) use ($request) {
                $query->where('city_id', $request->city);
            })
            ->get();
    }
}

````

And let's  write the test for it.

```php
php artisan make:test PropertySearchTest

````

In the test method, we create two properties with different cities and check that only ONE is returned from the search.

To create those fake properties, we also need to create a Factory for creating the test properties:

```php
php artisan make:factory PropertyFactory --model=Property
```

```php
    public function definition(): array
    {
        return [
            'owner_id' => User::where('role_id', Role::ROLE_OWNER)->value('id'),
            'name' => fake()->text(20),
            'city_id' => City::value('id'),
            'address_street' => fake()->streetAddress(),
            'address_postcode' => fake()->postcode(),
            'lat' => fake()->latitude(),
            'long' => fake()->longitude(),
        ];
    }
```

The method value('id') is a shorter way of doing ->first()->id.

tests/Feature/PropertySearchTest.php:

```php
    public function test_property_search_by_city_return_correct_result()
    {
        $owner = User::factory()->create(['role_id' => Role::ROLE_OWNER]);
        $cities = City::take(2)->pluck('id');
        $propertyInCity = Property::factory()->create(['owner_id' => $owner->id, 'city_id' => $cities[0]]);
        $propertyInAnotherCity = Property::factory()->create(['owner_id' => $owner->id, 'city_id' => $cities[1]]);

        $response = $this->getJson('api/v1/search?city='. $cities[0]);

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['id' => $propertyInCity->id]);
    }

````

### Search by Geographical Object

What if someone searches for a property near India Gate? No problem, if we know its coordinates and then launch the search by distance, with raw SQL condition.


Another new when() in the Controller, with adding a Geoobject search inside.


app/Http/Controllers/Api/v1/Public/PropertySearchController.php:

```php
class PropertySearchController extends Controller
{
    public function __invoke(Request $request)
    {
        return Property::with('city')
            ->when($request->city, function($query) use ($request) {
                $query->where('city_id', $request->city);
            })
            ->when($request->country, function($query) use ($request) {
                $query->whereHas('city', fn($q) => $q->where('country_id', $request->country));
            })
            ->when($request->geoobject, function($query) use ($request) {
                $geoobject = Geoobject::find($request->geoobject);
                if ($geoobject) {
                    $condition = "(
                        6371 * acos(
                            cos(radians(" . $geoobject->lat . "))
                            * cos(radians(`lat`))
                            * cos(radians(`long`) - radians(" . $geoobject->long . "))
                            + sin(radians(" . $geoobject->lat . ")) * sin(radians(`lat`))
                        ) < 10
                    )";
                    $query->whereRaw($condition);
                }
            })
            ->get();
    }
}


````

tests/Feature/PropertySearchTest.php:

````php
public function test_property_search_by_geoobject_returns_correct_results(): void
{
    $owner = User::factory()->create(['role_id' => Role::ROLE_OWNER]);
    $cityId = City::value('id');
    $geoobject = Geoobject::first();
    $propertyNear = Property::factory()->create([
        'owner_id' => $owner->id,
        'city_id' => $cityId,
        'lat' => $geoobject->lat,
        'long' => $geoobject->long,
    ]);
    $propertyFar = Property::factory()->create([
        'owner_id' => $owner->id,
        'city_id' => $cityId,
        'lat' => $geoobject->lat + 10,
        'long' => $geoobject->long - 10,
    ]);
 
    $response = $this->getJson('/api/search?geoobject=' . $geoobject->id);
 
    $response->assertStatus(200);
    $response->assertJsonCount(1);
    $response->assertJsonFragment(['id' => $propertyNear->id]);
}

`````