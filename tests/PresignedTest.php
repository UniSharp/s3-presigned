<?php

namespace Tests;

use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Unisharp\S3\Presigned\S3PresignedServiceProvider;
use Unisharp\S3\Presigned\S3Presigned;
use Unisharp\S3\Presigned\Exceptions\OptionsMissingException;

class PresignedTest extends TestCase
{
    protected $configs = [
        'credentials' => [
            'access_key' => 'access_key',
            'secret_key' => 'secret_key'
        ],
        'region' => 'ap-northeast-1',
        'version' => 'latest',
        'bucket' => 'bucket',
        'prefix' => 'prefix',
        'options' => [
            'foo' => 'bar'
        ]
    ];

    protected function setUp()
    {
        // 
    }

    public function testCheckOptions()
    {
        $options = [
            'bucket' => 'bucket',
            'prefix' => 'prefix',
            'options' => [
                'foo' => 'bar'
            ]
        ];
        $this->expectException(OptionsMissingException::class);
        $s3Presigned = $this->getS3Presigned($options);
    }

    public function testSetBaseUri()
    {
        $s3Presigned = $this->getS3Presigned();
        $bucket = $this->configs['bucket'];
        $prefix = $this->configs['prefix'];
        $baseUri = "https://s3-{$this->configs['region']}.amazonaws.com/{$bucket}/{$prefix}/";
        $this->assertEquals($baseUri, $s3Presigned->getBaseUri());
    }

    public function testGetClient()
    {
        $s3Presigned = $this->getS3Presigned();
        $this->assertInstanceOf(S3Client::class, $s3Presigned->getClient());
    }

    private function getS3Presigned($options = [])
    {
        $configs = $this->configs;
        $credentials = new Credentials(
            $configs['credentials']['access_key'],
            $configs['credentials']['secret_key']
        );
        $s3Client = new S3Client([
            'region'  => $configs['region'],
            'version' => $configs['version'],
            'credentials' => $credentials,
            'options' => [
                $configs['options']
            ]
        ]);

        $options = $options ?: $configs;

        return new S3Presigned($s3Client, $options['bucket'], $options['prefix'], $options);
    }
}