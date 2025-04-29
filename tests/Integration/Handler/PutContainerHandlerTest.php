<?php

declare(strict_types=1);

namespace App\Tests\Integration\Handler;

use App\Exception\InvalidContainerException;
use App\Exception\InvalidOperationException;
use App\Handler\PutContainerHandler;
use App\Tests\Integration\IntegrationTestCase;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Utils;

final class PutContainerHandlerTest extends IntegrationTestCase
{
    public function testInvalidContainerName(): void
    {
        $containerName = '--invalid-';

        $request = new ServerRequest('PUT', 'http://localhost/' . $containerName);
        $response = new Response();
        $args = ['container' => $containerName];

        /** @var PutContainerHandler $handler */
        $handler = $this->getContainer()->get(PutContainerHandler::class);
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

        /** @var PutContainerHandler $handler */
        $handler = $this->getContainer()->get(PutContainerHandler::class);
        ;

        $this->expectException(InvalidOperationException::class);
        $handler($request, $response, $args);
    }

    public function testCreateContainer(): void
    {
        // generate a random container name
        $containerName = uniqid('test-');

        //create the request URI
        $queryParams = ['op' => 'create'];
        $uri = sprintf('http://localhost/%s?%s', $containerName, http_build_query($queryParams));

        // create the server request and parsed arguments
        $request = (new ServerRequest('PUT', $uri))->withQueryParams($queryParams);
        $args = ['container' => $containerName];

        /** @var PutContainerHandler $handler */
        $handler = $this->getContainer()->get(PutContainerHandler::class);
        ;

        // invoke the handler
        $response = $handler($request, new Response(), $args);

        // assert the expected response status code
        $this->assertSame(201, $response->getStatusCode());

        // assert the container exists in the storage
        $client = $this->getBlobServiceClient()->getContainerClient($containerName);
        $this->assertTrue($client->exists());

        // delete the container from storage
        $client->delete();
    }

    public function testSetMetadata(): void
    {
        // generate a random container name
        $containerName = uniqid('test-');

        // create a new container in the storage account
        $client = $this->getBlobServiceClient()->getContainerClient($containerName);
        $client->create();

        // create the request URI
        $queryParams = ['op' => 'metadata'];
        $uri = sprintf('http://localhost/%s?%s', $containerName, http_build_query($queryParams));

        // create a new server request and parsed arguments
        $request = (new ServerRequest('PUT', $uri))->withQueryParams($queryParams)->withHeader('content-type', 'application/json');
        $args = ['container' => $containerName];

        // prepare the request body with metadata in JSON format
        $metadata = ['time' => time(), 'uniqid' => uniqid()];
        $contents = json_encode($metadata);
        $body = Utils::class::streamFor($contents);

        /** @var PutContainerHandler $handler */
        $handler = $this->getContainer()->get(PutContainerHandler::class);
        ;

        // invoke the handler
        $response = $handler($request->withBody($body), new Response(), $args);

        // assert the response status code
        $this->assertSame(200, $response->getStatusCode());

        // fetch properties from the container
        $properties = $client->getProperties();

        // assert that properties have same metadata
        foreach ($metadata as $key => $value) {
            $this->assertArrayHasKey($key, $properties->metadata);
            $this->assertSame((string) $value, $properties->metadata[$key]);
        }

        // delete the container
        $client->delete();
    }
}
