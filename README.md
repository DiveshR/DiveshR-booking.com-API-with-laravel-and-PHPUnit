

## Booking.com with Laravel

Re Creating Booking.com API with Laravel and PHPUnit.

- We will create a DB structure and API endpoints for managing properties, property search, showing apartments/rooms data, and making bookings.

- All of that is covered by automated tests with PHPUnit.


# Profile Fields Structure

## Goals of This Lesson

* Think about different ways of structuring profiles in DB
* Add the necessary fields

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
 