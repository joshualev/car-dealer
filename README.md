# Car Manufacturer Database

A Laravel 11 application for managing car manufacturers and their vehicles.

## Setup

```bash
# Clone and enter project
git clone <repository-url>
cd <project-directory>

# Install dependencies and compile assets
composer install
composer run dev

# Setup database
touch database/database.sqlite
cp .env.example .env
# Update .env to use SQLite:
# DB_CONNECTION=sqlite
# DB_DATABASE=/absolute/path/to/your/project/database/database.sqlite

php artisan key:generate
php artisan migrate
```

## Data Import

Import in this order:

1. Manufacturers:
```bash
php artisan import:manufacturers /path/to/manufacturers.csv
```

2. Cars:
```bash
php artisan import:cars /path/to/cars.csv
```

## CSV Formats

### manufacturers.csv
```csv
id,manufacturer,description,Origin Country
1,Mercedes-Benz,German luxury automaker known for pioneering automotive innovations...,German
2,Plymouth,Defunct American brand (1928-2001) owned by Chrysler...,United States
```

### cars.csv
```csv
id,manufacturer,model,year,colour
1,Mercedes-Benz,G-Class,2007,Mauv
2,Plymouth,Breeze,2000,Red
```

## Development

Make sure you bundle assets:
```bash
npm install
npm run dev
```

Start the server in another terminal:
```bash
php artisan serve
```
