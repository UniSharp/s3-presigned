AWS S3 Presigned SDK
==========
![php-badge](https://img.shields.io/badge/php-%3E%3D%205.6-8892BF.svg)
[![packagist-badge](https://img.shields.io/packagist/v/unisharp/s3-presigned.svg)](https://packagist.org/packages/unisharp/s3-presigned)

## Purpose
Traditionally to upload a file from users to a private S3 bucket needs two connections. One is from client to your own server, and the other is from your server to s3 bucket. Using pre-signed upload can solve this problem. Your server issuses pre-signed upload url for client to upload in advance, and the client can upload his file to s3 bucket directly. This package wraps pre-signed api for PHP and Laravel.

## Installation

```
composer require unisharp/s3-presigned
```

## Laravel 5

### Setup

Add ServiceProvider and Facade in `app/config/app.php`.

```
Unisharp\S3\Presigned\S3PresignedServiceProvider::class,
```

```
'S3Presigned' => Unisharp\S3\Presigned\Facades\S3Presigned::class,
```

> It supports package discovery for Laravel 5.5.

### Configuration

Add settings to **.env** file.

```
// required
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_S3_BUCKET=

// optional
AWS_REGION=ap-northeast-1
AWS_VERSION=latest
AWS_S3_PREFIX=
```

## API

```php
/*
 * @return string
 */
public function getSimpleUploadUrl($key, $minutes = 10, array $options = [], $guzzle = false)
```
* $key: your s3 file key, a prefix will be prepended automatically.
* $minutes: expire time for the pre-signed url.
* $options: see [AWS docs](http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#putobject) to find more.
* $guzzle: set true if you want to get a guzzle instance instead of string.

```php
/*
 * @return array('endpoint', 'inputs')
 */
public function getUploadForm($minutes = 10, array $policies = [], array $defaults = [])
```
* $minutes: expire time for the pre-signed url.
* $policies: see [AWS docs](http://docs.aws.amazon.com/AmazonS3/latest/API/sigv4-post-example.html) to find more.
* $defaults: default key-values you want to add to form inputs.

```php
/*
 * @return array
 */
public function listObjects($directory = '', $recursive = false)
```

```php
/*
 * @return boolean
 */
public function deleteObject($key)
```

```php
/*
 * @return string
 */
public function getBaseUri()
```

```php
/*
 * @return this
 */
public function setPrefix($prefix)
```

```php
/*
 * @return string
 */
public function getPrefix()
```

```php
/*
 * @return this
 */
public function setBucket($bucket)
```

```php
/*
 * @return string
 */
public function getBucket()
```

```php
/*
 * @return Aws\S3\S3Client
 */
public function getClient()
```
