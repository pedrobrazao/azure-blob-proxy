# Azure Blob Proxy

Proxy web application to expose Azure Blob Storage features over a simplified REST API.

> IMPORTANT! This application doesn't implement any authentication or any access control and it MUST be deployed under a private network or behind a firewall.

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

### List Containers

List all Containers in your Azure Storage account.

`GET /?op=list`

### Find Blobs by Tag in Account

`GET /?op=find&tags=tag1,tag2,...`

### List Blobs in Container

`GET /{container}?op=list&prefix=optional-prefix`

### Get Container Properties

`GET /{container}?op=props`

### Set Container Metadata

`PUT /{container}?op=metadata`

Body:

`{"key1":"value1","key2":"value2",...}`

### Find Blobs by Tag in Container

`GET /{container}?op=find&tags=tag1,tag2,...`

### Delete Container

`DELETE /{container}`

### Upload Blob

`PUT /{container}/{blob}?op=contents`

Headers:

- `Content-Type` The corresponding MIME type of the blob content.

Body:

The actual content of the blob.

### Download Blob

`GET /{container}/{blob}?op=content`

### Get Blob Properties

`GET /{container}/{blob}?op=props`

### Get Blob Tags

`GET /{container}/{blob}?op=tags`

### Set Blob Metadata

`PUT /{container}/{blob}?op=metadata`

Body:

`{"key1":"value1","key2":"value2",...}`

### Set Blob Tags

`PUT /{container}/{blob}?op=tags`

Body:

`[tag1,tag2,...]`

### Delete Blob

`DELETE /{container}/{blob}`

### Generate SAS URL for Blob

`GET /{container}/{blob}?op=sas&ttl=expiry-time-in-seconds&perms=rwd`
