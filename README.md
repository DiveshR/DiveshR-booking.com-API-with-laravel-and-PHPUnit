

## Booking.com with Laravel

Re Creating Booking.com API with Laravel and PHPUnit.

- We will create a DB structure and API endpoints for managing properties, property search, showing apartments/rooms data, and making bookings.

- All of that is covered by automated tests with PHPUnit.

## Lesson - 1 

- Create our simple DB structure for roles/permissions
- Create first API endpoints and PHPUnit Tests for registration
- Simulate API endpoints for logged-in users and write tests to check permissions
- Alternative: look at the spatie/laravel-permission package

 # Simple Permissions with Gates

- Manage properties
- Make booking
- Change the user's password
- etc.

* We will have three roles, at least:

- Administrator
- Property owner
- Simple User

* Roles and Permissions in the DB

```php
  php artisan make:model Role -ms
``````

* Migration:
$table->string('name');

- Make the name fillable:

* app/Models/Role.php:

```php 
protected $fillable = ['name']; 
``````

* Seeder to seed Roles

```php
  Role::create(['name' => 'Administrator']);
  Role::create(['name' => 'Property Owner']);
  Role::create(['name' => 'Simple User']);
``````
- Next, each role will have multiple Permissions. So let's store them in the database, too. The DB structure is identical to the Role:

```php
php artisan make:model Permission -m
``````

* Migration:
```php 
$table->string('name');
``````

* app/Models/Permission.php:

```php 
protected $fillable = ['name'];
``````

* Now, the relationship. It should be a many-to-many, because both each role may have many permissions, and also each permission may belong to many roles.

```php
php artisan make:migration create_permission_role_table
``````

* Migration:

$table->foreignId('permission_id')->constrained();
$table->foreignId('role_id')->constrained();

- And we add the methods for relationships, in both Models:

* app/Models/Role.php:

```php
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }
}
```

* app/Models/Permission.php:

```php
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function roles():BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
```
 - We have the relationship between roles and permissions

----------------------------------------------------------------------------------------

 # User: One Role or Multiple Roles?

 There are two layers of managing permissions:

* Admin adds the permissions and then specifies which roles have certain permissions
* For users, the admin/system assigns the ROLES to them, which in itself includes the permissions

* Add Role id to user table.

```php
php artisan make:migration add_role_id_to_users_table
```

Migration:

```php
  $table->foreignId('role_id')->after('email')->constrained();
```

app/Models/User.php:

```php
 class User extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];
 
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
```

So, at the moment of the registration, our new users will choose whether they are looking for a property to rent, or want to publish their own property for rent.

```php
php artisan make:seeder AdminUserSeeder
```


```php
php artisan make:seeder AdminUserSeeder
```

database/seeders/AdminUserSeeder.php:

```php
class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::class([
            'name' => 'Administrator',
            'email' => 'superadmin@gmail.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role_id' => 1, //Administrator
        ]);
    }
}
``````

Then, we add both seeders to the main DatabaseSeeder.
database/seeders/DatabaseSeeder.php:

```php
class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(RoleSeeder::class);
        $this->call(AdminUserSeeder::class);
    }
}
```
Launch the migrations with seeds:
```php
php artisan migrate --seed
```

Our next goal is to implement the registration API and test that users get their roles correctly.

-------------------------------------------------------------------------------------------------

# Registration API: Assign Permissions and Test Them

Let's say that we will have two registration forms: one for a simple user, and another for the property owner. So, let's simulate both of them and write automated tests that would check if users get the correct role/permissions.

Let's group them into one endpoint POST /api/v1/auth/register, 

We will create only one method and use the Controller as Invokable Single-Action Controller.

````php
php artisan make:controller Api/v1/Auth/RegisterController --invokable
``````
routes/api.php:

````php
Route::post('auth/register', App\Http\Controllers\Auth\RegisterController::class);
```````

- Create Form Requests

For more complex validation scenarios, you may wish to create a "form request". Form requests are custom request classes that encapsulate their own validation and authorization logic. 

````php
php artisan make:request api/v1/RegisterRequest
``````
```php
<?php

namespace App\Http\Requests\api\v1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required','string', 'max:255'],
            'email' => ['required','string','email','max:255','unique:users'],
            'password' => ['required','confirmed',Password::defaults()],
            'role_id' => ['required',Rule::in(2,3)],
        ];
    }
}

````````

- App\Http\Controllers\Api\v1\Auth

``````php
namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\v1\RegisterRequest;
use App\Models\User;

class RegisterController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role_id' => $request->role_id,
        ]);

        return response()->json([
            'access_token' => $user->createToken('client')->plainTextToken,
        ]);
    }
} `````

I'm assuming we use Laravel Sanctum for the Auth

So, after the validation, we can create the User with `role_id => 3`, which is the Simple User, according to our Seeder above

In the validation, we have that Rule::in(2, 3) hardcoded. You would probably agree that this 2, 3 isn't readable or understandable at the first glance to a new developer, as it's hard to remember which role is ID 2 or 3. Let's introduce a few constants inside a Role model.

```php
class Role extends Model
{
    const ROLE_ADMINISTRATOR = 1;
    const ROLE_OWNER = 2;
    const ROLE_USER = 3;
 
    // ...
}
````

Then in RegisterRequest:

````php
    public function rules(): array
    {
        return [
            // ...
            'role_id' => ['required', Rule::in(Role::ROLE_OWNER,Role::ROLE_USER)]
        ];
    }
````

Let's test how it works, by building automated test.

I edit the default phpunit.xml file to uncomment these two lines:

```php
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>

``````

NOTICE: For demo projects like this one, it's typically fine to use an in-memory database, but in the real-world scenario I often set up a separate testing MySQL database to run tests on

Let's generate our first test that would check if the registration works.

```php
php artisan make:test AuthTest

``````

tests/Feature/AuthTest.php:

````php

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_fails_with_admin_role()
    {
        $response = $this->postJson('api/v1/auth/register', [
            'name' => 'test',
            'email' => 'test@gmail.com',
            'password' => 'testPassword',
            'password_confirmation' => 'testPassword',
            'role_id' => Role::ROLE_ADMINISTRATOR,
        ]);

        $response->assertStatus(422);
    }

    public function test_registration_succeeds_with_owner_role()
    {
        $response = $this->postJson('api/v1/auth/register', [
            'name' => 'test Owner',
            'email' => 'testOwner@gmail.com',
            'password' => 'testPassword',
            'password_confirmation' => 'testPassword',
            'role_id' => Role::ROLE_OWNER,
        ]);
        $response->assertStatus(200)->assertJsonStructure([
            'access_token',
        ]);
    }

    public function test_registration_succeeds_with_user_role()
    {
        $response = $this->postJson('api/v1/auth/register',[
            'name' => 'test User',
            'email' => 'testUser@gmail.com',
            'password' => 'testPassword',
            'password_confirmation' => 'testPassword',
            'role_id' => Role::ROLE_USER,

        ]);
        $response->assertStatus(200)->assertJsonStructure([
            'access_token',
        ]);
    }
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}

``````

We have three methods, each of which tests the registration with each of the roles.
Now run test.

```php
php artisan test
``````