<?php

namespace Unisharp\S3\Presigned;

use Aws\S3\S3Client;
use Aws\S3\PostObjectV4;
use Unisharp\S3\Presigned\Exceptions\OptionsMissingException;

class S3Presigned
{
    protected $client;
    protected $region;
    protected $bucket;
    protected $options;
    protected $requiredOptions = [];
    protected $baseUri;
    protected $prefix;

    public function __construct(S3Client $client, $region, $bucket, $prefix = '', array $options = [])
    {
        $this->client = $client;
        $this->bucket = $bucket;
        $this->region = $region;
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

    public function getUploadForm($minutes = 10, array $policies = [], array $defaults = [])
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
        $policies = $policies ?: $defaultPolicies;
        $postObject = $this->getPostObject($defaults, $policies, $minutes);

        return [
            'endpoint' => $this->getBaseUri(),
            'inputs' => $postObject->getFormInputs()
        ];
    }

    public function listObjects($directory = '', $recursive = false)
    {
        // http://docs.aws.amazon.com/AmazonS3/latest/dev/ListingObjectKeysUsingPHP.html
        // http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#listobjects
        $options = [
            'Bucket' => $this->getBucket(),
            'Prefix' => $this->getPrefix()
        ];
        if ($recursive === false) {
            $options['Delimiter'] = '/';
        }
        $listing = $this->retrievePaginatedListing($options);
        $normalized = array_map([$this, 'normalizeObject'], $listing);

        return $normalized;
    }

    protected function retrievePaginatedListing(array $options)
    {
        $resultPaginator = $this->client->getPaginator('ListObjects', $options);

        $listing = [];
        foreach ($resultPaginator as $result) {
            $listing = array_merge($listing, $result->get('Contents') ?: []);
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

    protected function normalizeObject(array $object)
    {
        if (array_key_exists('LastModified', $object)) {
            $object['Timestamp'] = strtotime($object['LastModified']);
        }
        $object['Url'] = $this->getBaseUri() . $object['Key'];

        return $object;
    }

    protected function getPostObject(array $defaults, array $options, $minutes = 10)
    {
        return new PostObjectV4(
            $this->getClient(),
            $this->getBucket(),
            $defaults,
            $options,
            "+{$minutes} minutes"
        );
    }

    protected function checkOptions()
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
        $this->baseUri = "https://{$this->bucket}.s3-{$this->region}.amazonaws.com/";

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
