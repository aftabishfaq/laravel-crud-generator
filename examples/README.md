# Examples

Using a path repository in a Laravel app:

Add to your app's composer.json:

```
{
  "repositories": [
    { "type": "path", "url": "../laravel-crud" }
  ]
}
```

Then require the package:

```
composer require aftab/laravel-crud:dev-main
```

Copy `examples/crud.yaml` to your app root and run:

```
php artisan crud:execute crud.yaml --generate --migrate
```

Visit `/crud/users`.
