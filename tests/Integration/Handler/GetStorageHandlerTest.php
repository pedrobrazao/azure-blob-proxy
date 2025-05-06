<?php

declare(strict_types=1);

namespace App\Tests\Integration\Handler;

use App\Exception\InvalidOperationException;
use App\Handler\GetStorageHandler;
use App\Tests\Integration\IntegrationTestCase;
use AzureOss\Storage\Blob\Models\UploadBlobOptions;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;

final class GetStorageHandlerTest extends IntegrationTestCase
{
    public function testInvalidOperation(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/');
        $response = new Response();
        $args = [];

        /** @var GetStorageHandler $handler */
        $handler = $this->getContainer()->get(GetStorageHandler::class);
        ;

        $this->expectException(InvalidOperationException::class);
        $handler($request, $response, $args);
    }

    public function testListContainers(): void
    {
        // create some containers
        $containers = [];
        $client = $this->getBlobServiceClient();
        for ($i = 0; $i < random_int(2, 10); $i++) {
            $name = uniqid('test-');
            $client->getContainerClient($name)->create();
            $this->assertTrue($client->getContainerClient($name)->exists());
            $containers[] = $name;
        }

        // create server request
        $queryParams = ['op' => 'list'];
        $uri = sprintf('http://localhost/?%s', http_build_query($queryParams));
        $request = (new ServerRequest('GET', $uri))->withQueryParams($queryParams);

        /** @var GetStorageHandler $handler */
        $handler = $this->getContainer()->get(GetStorageHandler::class);

        // invoke handler and hold the response
        $response = $handler($request, new Response(), []);

        // assert expected response status code
        $this->assertSame(200, $response->getStatusCode());

        // decode and assert response body
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);
        $this->assertIsArray($data);

        foreach ($containers as $name) {
            $this->assertArrayHasKey($name, $data);
        }

        // delete the containers
        foreach ($containers as $name) {
            $client->getContainerClient($name)->delete();
        }
    }

    public function testFindBlobsByTag(): void
    {
        $containerName = uniqid('test-');
        $containerClient = $this->getBlobServiceClient()->getContainerClient($containerName);
        $containerClient->create();
        $this->assertTrue($containerClient->exists());

        // create some blobs with tags
        $blobs = [];
        for ($i = 0; $i < random_int(5, 20); $i++) {
            $name = sprintf('blob%s.txt', $i);
            ;
            $blobs[$name] = ['tag1' => 'b' . $i, 'tag2' => (string) ($i % 2)];
            $blobClient = $containerClient->getBlobClient($name);
            $blobClient->upload(random_bytes(random_int(10, 20)), new UploadBlobOptions('text/plain'));
            $this->assertTrue($blobClient->exists());
        }

        // create server request
        $queryParams = ['op' => 'find', 'where' => 'tag2=\'0\''];
        $uri = sprintf('http://localhost/?%s', http_build_query($queryParams));
        $request = (new ServerRequest('GET', $uri))->withQueryParams($queryParams);

        /** @var GetStorageHandler $handler */
        $handler = $this->getContainer()->get(GetStorageHandler::class);

        // invoke handler and hold the response
        $response = $handler($request, new Response(), []);

        // assert the expected response status code
        $this->assertSame(200, $response->getStatusCode());

        // decode response body and assert blob tags
        $json = $response->getBody()->getContents();
        $data = json_decode($json, true);
        $this->assertIsArray($data);

        // delete the container
        $containerClient->delete();
    }
}
