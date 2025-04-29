<?php

declare(strict_types=1);

namespace App\Tests\Integration\Handler;

use App\Exception\InvalidContainerException;
use App\Exception\InvalidOperationException;
use App\Handler\GetContainerHandler;
use App\Tests\Integration\IntegrationTestCase;
use AzureOss\Storage\Blob\Models\UploadBlobOptions;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;

final class GetContainerHandlerTest extends IntegrationTestCase
{
    public function testInvalidContainerName(): void
    {
        $containerName = '--invalid-';

        $request = new ServerRequest('PUT', 'http://localhost/' . $containerName);
        $response = new Response();
        $args = ['container' => $containerName];

        /** @var GetContainerHandler $handler */
        $handler = $this->getContainer()->get(GetContainerHandler::class);
        ;

        $this->expectException(InvalidContainerException::class);
        $handler($request, $response, $args);
    }

    public function testInvalidOperation(): void
    {
        $containerName = 'my-container';

        $request = new ServerRequest('PUT', 'http://localhost/' . $containerName);
        $response = new Response();
        $args = ['container' => $containerName];

        /** @var GetContainerHandler $handler */
        $handler = $this->getContainer()->get(GetContainerHandler::class);
        ;

        $this->expectException(InvalidOperationException::class);
        $handler($request, $response, $args);
    }


    #[DataProvider('prefixProvider')]
    public function testListBlobs(string $prefix): void
    {
        // generate a random container name
        $containerName = uniqid('test-');

        // create the container in the storage
        $client = $this->getBlobServiceClient()->getContainerClient($containerName);
        $client->create();
        $this->assertTrue($client->exists());

        // generate a list of blob names to be stored in the container
        $blobs = [];

        // ... some are not prefixed
        for ($i = 0; $i < rand(1, 9); $i++) {
            $blobs[uniqid()] = random_bytes(rand(100, 1000));
        }

        // ... some are prefixed
        for ($i = 0; $i < rand(1, 9); $i++) {
            $blobs[uniqid($prefix)] = random_bytes(rand(100, 1000));
        }

        // put the blobs to the container
        foreach ($blobs as $name => $contents) {
            $client->getBlobClient($name)->upload($contents, new UploadBlobOptions('text/plain'));
        }

        // create server request and parsed arguments
        $queryParams = ['op' => 'list', 'prefix' => $prefix];
        $uri = sprintf('http://localhost/%s?%s', $containerName, http_build_query($queryParams));
        $request = (new ServerRequest('GET', $uri))->withQueryParams($queryParams);
        $args = ['container' => $containerName];

        /** @var GetContainerHandler $handler */
        $handler = $this->getContainer()->get(GetContainerHandler::class);

        // invoke the handler and hold the response
        $response = $handler($request, new Response(), $args);

        // assert the expected response status code
        $this->assertSame(200, $response->getStatusCode());

        // assert all blobs are listed in the response body
        $data = json_decode($response->getBody()->getContents(), true);
        foreach ($data as $blob) {
            $this->assertArrayHasKey($blob->name, $blobs);
        }

        // delete the container and all blobs
        $client->delete();
    }

    public static function prefixProvider(): array
    {
        return [
            [''],
            ['foo/'],
        ];
    }

    public function testGetContainerProperties(): void
    {
        // generate a random container name
        $containerName = uniqid('test-');

        // create the container and set the metadata
        $client = $this->getBlobServiceClient()->getContainerClient($containerName);
        $client->create();


        // assert the container exists
        $this->assertTrue($client->exists());

        // set metadata
        $metadata = ['time' => time(), 'id' => uniqid()];
        $client->setMetadata($metadata);

        // assert the metadata is stored
        $properties = $client->getProperties();
        $this->assertCount(count($metadata), $properties->metadata);

        // create server request and parsed arguments
        $queryParams = ['op' => 'props'];
        $uri = sprintf('http://localhost/%s?%s', $containerName, http_build_query($queryParams));
        $request = (new ServerRequest('GET', $uri))->withQueryParams($queryParams);
        $args = ['container' => $containerName];

        /** @var GetContainerHandler $handler */
        $handler = $this->getContainer()->get(GetContainerHandler::class);

        // invoke handler and hold the response
        $response = $handler($request, new Response(), $args);

        // assert the expected response status code
        $this->assertSame(200, $response->getStatusCode());

        // decode the response body
        $contents = $response->getBody()->getContents();
        $data = json_decode($contents, true);

        // assert the response contains data
        $this->assertIsArray($data);
        $this->assertArrayHasKey('lastModified', $data);
        $this->assertArrayHasKey('metadata', $data);
        foreach ($metadata as $key => $value) {
            $this->assertArrayHasKey($key, $data['metadata']);
            $this->assertSame((string) $value, $data['metadata'][$key]);
        }

        // delete the container
        $client->delete();
    }
}
