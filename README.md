# Azure Blob Proxy

This repository offers a proxy web application which makes easy to access an Azure Blob Storage account using Shared Access Key and exposing its features over a simplified REST API.

## Requirements

- PHP 8.2+
- NPM 8.10+ (only in development mode)

## Setup

Run these commands from the project root directory in order to install external dependencies:

`composer install`

`npm install`

Setup Apache or Nginx document root to `public/index.php`.

Copy `config/settings.php` to `config/settings.local.php` and adjust values accordingly.

Alternatively in development mode simply start Azurite as Azure emulator and PHP as local web server:

`node_modules/.bin/azurite --silent --location var/storage/ --debug var/storage/debug.log
`

`php -S localhost:8000 -t public public/index.php`

## Endpoints

`GET /` List all Containers in your Azure Storage account.

`GET /{container}` List all objects (a.k.a. blobs) inside the designated container.

`GET /{container}/{blob}` Get the content of the specified blob.