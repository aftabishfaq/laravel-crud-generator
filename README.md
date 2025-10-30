## Laravel CRUD Generator (Package)

![CI](https://github.com/aftabishfaq/laravel-crud/actions/workflows/tests.yml/badge.svg)

### Installation

1) Require the package (path repo during development or from Packagist once published):
```bash
composer require aftab/laravel-crud
```

2) Register (Laravel auto-discovery should handle this; otherwise add the provider):
`Aftab\LaravelCrud\CrudGeneratorServiceProvider`

3) Publish assets (optional):
```bash
php artisan vendor:publish --tag=crud-config
php artisan vendor:publish --tag=crud-views
```

### Quickstart

Create `crud.yaml` at your project root:
```yaml
tables:
  - name: users
    display_name: Users
    timestamps: true
    soft_deletes: true
    columns:
      - { name: name, type: string, validation: "string|max:255" }
      - { name: email, type: string, unique: true, validation: "email|max:255" }
      - { name: status, type: enum, options: [active, inactive], default: active }
```

Validate, generate, and migrate:
```bash
php artisan crud:execute crud.yaml --generate --migrate
```

Open dynamic CRUD UI:
`/crud/users`

### CLI Usage

```bash
php artisan crud:execute {file?} {--generate} {--migrate} {--force} {--dry-run} {--input=} {--temp}
```
- `file`: Path to JSON/YAML (defaults to `crud.yaml` if present)
- `--generate`: Write migrations and models
- `--migrate`: Run migrations after generation (asks unless `--force`)
- `--dry-run`: Validate and print plan only
- `--input`: Inline JSON/YAML payload
- `--temp`: Write migrations to a temp folder

Helpers:
```bash
php artisan crud:preview crud.yaml
php artisan crud:rollback --yes
```

### Publish Views/Config

```bash
php artisan vendor:publish --tag=crud-config
php artisan vendor:publish --tag=crud-views
```

See the config reference below for available options.

### Config: `config/crud.php`

- **route_prefix**: URL prefix for CRUD routes. Example: `crud` → `/crud/users`.
- **middleware**: Middleware array applied to CRUD routes. Default: `['web']`.
- **models_namespace**: Namespace for generated models. Default: `App\\Models`.
- **migrations_path**: Relative path for generated migrations. Default: `database/migrations/crud`.
- **views_path**: Relative path for published/overridden views. Default: `resources/views/vendor/laravel-crud`.
- **auto_migrate**: When `true`, runs `migrate` after generation. Default: `false`.
- **preview_mode**: When `true`, simulates changes without writing files. Default: `false`.
- **allowed_tables**: Optional allow-list for dynamic routes. When empty, any existing DB table is allowed.
- **custom_validation**: Map table/column to custom validation, e.g. `['users' => ['email' => 'email|unique:users,email']]`.
- **uploads**: Configure file uploads. Example:
  - `uploads.disk`: Filesystem disk (default: `public`).
  - `uploads.fields`: Map `table.column` to `['path' => 'uploads/avatars']`.
- **field_sources**: Configure select/dropdown options per `table.column`:
  - Static: `['type' => 'static', 'options' => ['active' => 'Active']]`
  - Table: `['type' => 'table', 'table' => 'groups', 'value' => 'id', 'label' => 'name']`
  - API: `['type' => 'api', 'callback' => '\\App\\Support\\OptionCallbacks::countries']`
- **permissions**:
  - `enabled`: when true, authorizes using Gate per action with ability names supporting `:table` placeholder.
  - `abilities`: map actions (`index`, `show`, `create`, `store`, `edit`, `update`, `destroy`) to ability strings.
- **audit**:
  - `enabled`: when true, automatically set `created_by`/`updated_by` columns if they exist.

### Publishing

- Publish config: `php artisan vendor:publish --tag=crud-config`
- Publish views: `php artisan vendor:publish --tag=crud-views`


### More Docs

- See `docs/usage.md` for end-to-end workflow
- See `docs/relationships.md` for relationships and pivots
- See `docs/customization.md` for customizing views and adding field types
- See `docs/security.md` for security best practices

### Examples

Check `examples/` for a sample `crud.yaml` and instructions to link this package into a Laravel app via Composer path repositories.

### Security & Production Considerations

- Validate inputs: the parser and validator reject invalid schemas; never execute arbitrary code from config. API option callbacks are disabled by default (`field_sources_allow_api_callbacks=false`).
- Database safety: when using `--migrate`, a confirmation is required unless `--force`. Take backups before running migrations.
- SQL injection: all queries use Eloquent/Query Builder. Do not interpolate raw SQL from config.
- File uploads: prefer local files or authenticated endpoints; configure `uploads` and sanitize inputs.
- Rate limiting: protect dynamic CRUD routes by adding throttling to `config('crud.middleware')`, e.g. `['web','throttle:60,1']`.
