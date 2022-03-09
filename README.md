# lumen-passport
[![Total Downloads](https://poser.pugx.org/dusterio/lumen-passport/d/total.svg)](https://packagist.org/packages/imanrjb/passport-auth)
[![Latest Stable Version](https://poser.pugx.org/dusterio/lumen-passport/v/stable.svg)](https://packagist.org/packages/imanrjb/passport-auth)
[![Latest Unstable Version](https://poser.pugx.org/dusterio/lumen-passport/v/unstable.svg)](https://packagist.org/packages/imanrjb/passport-auth)
[![License](https://poser.pugx.org/dusterio/lumen-passport/license.svg)](https://packagist.org/packages/imanrjb/passport-auth)

Making Laravel Passport work with Lumen

A simple service provider that makes Laravel Passport work with Lumen

## Dependencies

* PHP >= 8.0
* Lumen >= 9.0

## Installation via Composer
```bash
$ composer require imanrjb/passport-auth
```

Or if you prefer, edit `composer.json` manually:

```json
{
    "require": {
        "imanrjb/passport-auth": "^1.0"
    }
}
```

### Modify the bootstrap flow (```bootstrap/app.php``` file)

```php
// Enable Facades
$app->withFacades();

// Enable Eloquent
$app->withEloquent();

// Enable auth middleware (shipped with Lumen)
$app->routeMiddleware([
    'auth' => App\Http\Middleware\Authenticate::class,
]);

$app->register(App\Providers\AuthServiceProvider::class);
$app->register(\PassportAuth\PassportAuthServiceProvider::class);
```

## Registering Routes

Next, you should call the LumenPassport::routes method within the boot method of your application (AuthServiceProvider.php).
This method will register the routes necessary to issue access tokens and revoke access tokens, clients, and personal access tokens:

```php
\PassportAuth\LumenPassport::routes($this->app->router);
```

You can add that into an existing group, or add use this route registrar independently like so;

```php
\PassportAuth\LumenPassport::routes($this->app->router, ['prefix' => 'v1/oauth']);
```

### Migrate and install Laravel Passport

```bash
# Publish config files
php artisan vendor:publish --tag=passport-auth

# Create new tables for Passport
php artisan migrate

# Install encryption keys and other necessary stuff for Passport
php artisan passport:install
```

### Installed routes

This package mounts the following routes after you call routes() method (see instructions below):

Verb | Path | NamedRoute | Controller | Action | Middleware
--- | --- | --- | --- | --- | ---
POST   | /oauth/token                             |            | \Laravel\Passport\Http\Controllers\AccessTokenController           | issueToken | -
GET    | /oauth/tokens                            |            | \Laravel\Passport\Http\Controllers\AuthorizedAccessTokenController | forUser    | auth
DELETE | /oauth/tokens/{token_id}                 |            | \Laravel\Passport\Http\Controllers\AuthorizedAccessTokenController | destroy    | auth
POST   | /oauth/token/refresh                     |            | \Laravel\Passport\Http\Controllers\TransientTokenController        | refresh    | auth
GET    | /oauth/clients                           |            | \Laravel\Passport\Http\Controllers\ClientController                | forUser    | auth
POST   | /oauth/clients                           |            | \Laravel\Passport\Http\Controllers\ClientController                | store      | auth
PUT    | /oauth/clients/{client_id}               |            | \Laravel\Passport\Http\Controllers\ClientController                | update     | auth
DELETE | /oauth/clients/{client_id}               |            | \Laravel\Passport\Http\Controllers\ClientController                | destroy    | auth
GET    | /oauth/scopes                            |            | \Laravel\Passport\Http\Controllers\ScopeController                 | all        | auth
GET    | /oauth/personal-access-tokens            |            | \Laravel\Passport\Http\Controllers\PersonalAccessTokenController   | forUser    | auth
POST   | /oauth/personal-access-tokens            |            | \Laravel\Passport\Http\Controllers\PersonalAccessTokenController   | store      | auth
DELETE | /oauth/personal-access-tokens/{token_id} |            | \Laravel\Passport\Http\Controllers\PersonalAccessTokenController   | destroy    | auth

Please note that some of the Laravel Passport's routes had to 'go away' because they are web-related and rely on sessions (eg. authorise pages). Lumen is an
API framework so only API-related routes are present.



## User model

Make sure your user model uses Passport's ```HasApiTokens``` trait, eg.:

```php
class User extends Model
{
    use HasApiTokens, Authenticatable, Authorizable;

    public function findForPassport($email)
    {
        return $this->where('email', $email)->first();
    }

    public function validateForPassportPasswordGrant($password)
    {
        return Hash::check($password, $this->password);
    }
}
```

### Different TTLs for different password clients

Laravel Passport allows to set one global TTL for access tokens, but it may be useful sometimes
to set different TTLs for different clients (eg. mobile users get more time than desktop users).

Simply do the following in your service provider:

```php
// Second parameter is the client Id
LumenPassport::tokensExpireIn(Carbon::now()->addYears(50), 2); 
```

If you don't specify client Id, it will simply fall back to Laravel Passport implementation.

### Console command for purging expired tokens

Simply run ```php artisan passport:purge``` to remove expired refresh tokens and their corresponding access tokens from the database.


## Issue token

```php
// Generate new token with user credential
    $client = Client::whereProvider('users')->first();

    $request = Request::create('/oauth/token', 'POST', [
        'grant_type' => 'password',
        'client_id' => $client->id,
        'client_secret' => $client->secret,
        'username' => $request->email,
        'password' => $request->password,
        'scope' => '*',
        'user_agent' => Browser::platformName() . ", " . Browser::browserFamily(),
        'ip' => request()->ip()
    ]);

    return app()->handle($request);


// Create route with middleware and return user information
    $router->group(['middleware' => 'auth:api'], function () use ($router) {
        $router->get('/user', function () {
            return \Illuminate\Support\Facades\Auth::user();
        });
    });
```

## Refresh token

```php
// Generate new token with refresh token
    $client = Client::whereProvider('users')->first();

    $request = Request::create('/oauth/token', 'POST', [
        'grant_type' => 'refresh_token',
        'client_id' => $client->id,
        'client_secret' => $client->secret,
        'refresh_token' => $request->refresh_token,
        'scope' => '',
    ]);

    return app()->handle($request);
```