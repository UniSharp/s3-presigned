<?php

namespace Unisharp\S3\Presigned;

use Aws\S3\S3Client;
use Aws\S3\PostObjectV4;
use Unisharp\S3\Presigned\Exceptions\OptionsMissingException;

class S3Presigned
{
    protected $client;
    protected $options;
    protected $requiredOptions = ['region'];
    protected $baseUri;
    protected $prefix;

    public function __construct(S3Client $client, $bucket, $prefix = '', array $options = [])
    {
        $this->client = $client;
        $this->bucket = $bucket;
        $this->options = $options;
        $this->checkOptions();
        $this->setBaseUri();
        $this->setPrefix($prefix);
    }

    public function getSimpleUploadUrl($key, $minutes = 10, array $options = [], $guzzle = false)
    {
        // http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#putobject
        // http://docs.aws.amazon.com/AmazonS3/latest/API/sigv4-query-string-auth.html
        $defaults = [
            'Bucket' => $this->getBucket(),
            'Key' => $this->getPrefix() . $key,
            'ACL' => 'public-read'
        ];
        $options = $options ? array_merge($defaults, $options) : $defaults;
        $cmd = $this->client->getCommand('PutObject', $options);
        $request = $this->client
            ->createPresignedRequest($cmd, "+{$minutes} minutes");
        $result = $request->getUri();

        return $guzzle ? $result : (string) $result;
    }

    public function getUploadForm($key, $minutes = 10, array $policies = [], array $defaults = [])
    {
        // https://aws.amazon.com/tw/articles/browser-uploads-to-s3-using-html-post-forms/
        // http://docs.aws.amazon.com/AmazonS3/latest/API/sigv4-post-example.html
        $overrides = [
            'key' => $this->getPrefix() . '${filename}'
        ];
        $defaults = $defaults ? array_merge($overrides, $defaults) : $overrides;
        $defaultPolicies = [
            ['acl' => 'public-read'],
            ['bucket' => $this->getBucket()],
            ['starts-with', '$key', $this->getPrefix()]
        ];
        $policies = $policies ? array_merge($defaultPolicies, $defaults) : $defaultPolicies;
        $postObject = $this->getPostObject($defaults, $policies, $minutes);

        return [
            'endpoint' => $this->getBaseUri(),
            'inputs' => $postObject->getFormInputs()
        ];
    }

    public function listObjects()
    {
        // http://docs.aws.amazon.com/AmazonS3/latest/dev/ListingObjectKeysUsingPHP.html
        // http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#listobjects
        // https://github.com/thephpleague/flysystem-aws-s3-v3/blob/master/src/AwsS3Adapter.php
        $resultPaginator = $this->client->getPaginator('ListObjects', [
            'Bucket' => $this->getBucket(),
            'Prefix' => $this->getPrefix()
        ]);

        $listing = [];
        foreach ($resultPaginator as $result) {
            $objects = $result->get('Contents');
            if (is_null($objects)) {
                continue;
            }
            foreach ($objects as $object) {
                $listing[] = $this->normalizeObject($object);
            }
        }

        return $listing;
    }

    public function deleteObject($key)
    {
        return $this->client->deleteObject([
            'Bucket' => $this->getBucket(),
            'Key'    => $key
        ]);
    }

    private function normalizeObject(array $object)
    {
        $normalized = [];
        $normalized['key'] = $object['Key'] ?? '';
        $normalized['url'] = $this->baseUri . $normalized['key'];
        $normalized['size'] = $object['Size'] ?? '';

        return $normalized;
    }

    private function getPostObject(array $defaults, array $options, $minutes = 10)
    {
        return new PostObjectV4(
            $this->getClient(),
            $this->getBucket(),
            $defaults,
            $options,
            "+{$minutes} minutes"
        );
    }

    public function checkOptions()
    {
        $missings = array_filter($this->requiredOptions, function ($value) {
            return !array_key_exists($value, $this->options);
        });
        if (count($missings)) {
            $fields = implode(', ', $missings);
            throw new OptionsMissingException("`{$fields}` field(s) is required in options");
        }
    }

    public function setBaseUri()
    {
        $baseUri = "https://{$this->bucket}.s3-{$this->options['region']}.amazonaws.com/";
        $this->baseUri = $baseUri;

        return $this;
    }

    public function getBaseUri()
    {
        return $this->baseUri;
    }

    public function getPrefixedUri()
    {
        return $this->getBaseUri() . $this->getPrefix();
    }

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function setBucket($bucket)
    {
        $this->bucket = $bucket;

        return $this;
    }

    public function getBucket()
    {
        return $this->bucket;
    }

    public function getClient()
    {
        return $this->client;
    }
}
