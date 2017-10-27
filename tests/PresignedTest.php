<?php

namespace Tests;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Aws\S3\PostObjectV4;
use Aws\Api\DateTimeResult;
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
        'prefix' => 'prefix/',
        's3_client' => [
            'options' => []
        ],
        'options' => [
            'foo' => 'bar'
        ]
    ];

    protected function setUp()
    {
        parent::setUp();
    }

    // public function testCheckOptions()
    // {
    //     $configs = [
    //         'bucket' => 'bucket',
    //         'prefix' => 'prefix/',
    //         'options' => [
    //             'foo' => 'bar'
    //         ]
    //     ];
    //     $this->expectException(OptionsMissingException::class);
    //     $s3Presigned = $this->getS3Presigned($configs);
    // }

    public function testSetPrefix()
    {
        $s3Presigned = $this->getS3Presigned();
        $bucket = $this->configs['bucket'];
        $prefix = $this->configs['prefix'];
        $region = $this->configs['region'];
        $baseUri = "https://{$bucket}.s3-{$region}.amazonaws.com/{$prefix}";
        $this->assertEquals($baseUri, $s3Presigned->getPrefixedUri());
    }

    public function testGetClient()
    {
        $s3Presigned = $this->getS3Presigned();
        $this->assertInstanceOf(S3Client::class, $s3Presigned->getClient());
    }

    public function testGetSimpleUploadUrl()
    {
        $filename = 'filename.extension';
        $host = 'bucket.s3-ap-northeast-1.amazonaws.com';
        $path = "/prefix/{$filename}";
        $s3Presigned = $this->getS3Presigned();
        $result = $s3Presigned->getSimpleUploadUrl($filename, 10, [], true);
        $this->assertEquals($host, $result->getHost());
        $this->assertEquals($path, $result->getPath());
    }

    public function testGetUploadForm()
    {
        $policies = [];
        $defaults = ['foo' => 'bar'];
        $s3Presigned = $this->getS3Presigned();
        $result = $s3Presigned->getUploadForm(10, $policies, $defaults);
        $this->assertArrayHasKey('endpoint', $result);
        $this->assertArrayHasKey('inputs', $result);
        $this->assertEquals($result['endpoint'], $s3Presigned->getBaseUri());
        $this->assertEquals($result['inputs']['foo'], 'bar');
    }

    public function testListObjects()
    {
        $number = 10;
        $url = "https://{$this->configs['bucket']}.s3-ap-northeast-1.amazonaws.com/public/";
        $s3Client = m::mock(S3Client::class);
        $s3Client->shouldReceive('getPaginator')
            ->once()
            ->with('ListObjects', m::type('array'))
            ->andReturn([$this->getMockedObjects($number)]);

        $s3Presigned = $this->getS3Presigned([], $s3Client);
        $objects = $s3Presigned->listObjects();
        $this->assertEquals($number , count($objects));
        $this->assertEquals($url, $objects[0]['Url']);
    }

    protected function getMockedObjects($number = 5)
    {
        $objects = m::mock(\stdObject::class);
        $objects->shouldReceive('get')
            ->once()
            ->with('Contents')
            ->andReturn(array_fill(0, $number, [
                'Key' => 'public/',
                'LastModified' => DateTimeResult::fromEpoch(time()),
                'ETag' => 'etag',
                'Size' => 0,
                'StorageClass' => 'STANDARD',
                'Owner' => [
                    'DisplayName' => 'seafood',
                    'ID' => 'owner_id',
                ]
            ]));

        return $objects;
    }

    protected function getS3Presigned(array $configs = [], S3Client $s3Client = null)
    {
        $configs = array_merge($this->configs, $configs);
        $s3Client = $s3Client ? $s3Client : $this->getS3Client($configs);

        return new S3Presigned(
            $s3Client,
            $configs['region'],
            $configs['bucket'],
            $configs['prefix'],
            $configs['options']
        );
    }

    protected function getS3Client(array $configs)
    {
        $credentials = new Credentials(
            $configs['credentials']['access_key'],
            $configs['credentials']['secret_key']
        );

        return new S3Client([
            'region'  => $configs['region'],
            'version' => $configs['version'],
            'credentials' => $credentials,
            'options' => [
                $configs['s3_client']['options']
            ]
        ]);
    }
}