

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

```php protected $fillable = ['name']; ``````

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
```php $table->string('name');``````

* app/Models/Permission.php:

```php protected $fillable = ['name'];``````

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