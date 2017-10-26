<?php

namespace Tests;

use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Unisharp\S3\Presigned\S3PresignedServiceProvider;
use Unisharp\S3\Presigned\S3Presigned;

class PresignedTest extends TestCase
{
    protected function setUp()
    {
        //
    }

    public function testConstructor()
    {
        $this->assertTrue(true);
    }
}