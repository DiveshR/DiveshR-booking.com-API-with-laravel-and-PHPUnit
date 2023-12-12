

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

protected $fillable = ['name'];

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
$table->string('name');

* app/Models/Permission.php:

protected $fillable = ['name'];

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
