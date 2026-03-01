# Commands to run after adding migrations

# From the application (host) project, inside ddev or your environment:
# 1) Regenerate autoload so composer sees new files:
composer dump-autoload

# 2) (Optional) Publish migrations so host app can inspect/modify them:
php artisan vendor:publish --provider="Packages\FormBuilder\FormBuilderServiceProvider" --tag="form-builder-migrations"

# 3) Run the migrations:
php artisan migrate

# If using ddev, prefix with ddev exec or ddev composer as appropriate:
# ddev exec composer dump-autoload
# ddev exec php artisan vendor:publish --provider="Packages\\FormBuilder\\FormBuilderServiceProvider" --tag="form-builder-migrations"
# ddev exec php artisan migrate
