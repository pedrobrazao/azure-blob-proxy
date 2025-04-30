<?php

declare(strict_types=1);

namespace App\Tests\Integration\Handler;

use App\Exception\InvalidBlobException;
use App\Exception\InvalidContainerException;
use App\Exception\InvalidOperationException;
use App\Handler\GetBlobHandler;
use App\Tests\Integration\IntegrationTestCase;
use AzureOss\Storage\Blob\Models\UploadBlobOptions;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\Attributes\DataProvider;

final class GetBlobHandlerTest extends IntegrationTestCase
{
    #[DataProvider('argumentsProvider')]
    public function testInvalidArguments(string $containerName, string $blobName, array $queryParams, string $expectedException): void
    {
        $uri = sprintf('http://localhost/%s/%s', $containerName, $blobName);
        $request = (new ServerRequest('GET', $uri))->withQueryParams($queryParams);
        $response = new Response();
        $args = ['container' => $containerName, 'blob' => $blobName];

        /** @var GetBlobHandler $handler */
        $handler = $this->getContainer()->get(GetBlobHandler::class);
        ;

        $this->expectException($expectedException);
        $handler($request, $response, $args);
    }

    public static function argumentsProvider(): array
    {
        return [
            ['-invalid-', 'foo/bar.txt', ['op' => 'content'], InvalidContainerException::class],
            ['my-container', '', ['op' => 'content'], InvalidBlobException::class],
            ['my-container', 'foo/bar.txt', ['op' => 'INVALID'], InvalidOperationException::class],
        ];
    }

    public function testGetBlobContents(): void
    {
        // generate random container and blob names
        $containerName = uniqid('test-');
        $blobName = sprintf('%s/%s.txt', uniqid(), uniqid());

        // create the container
        $containerClient = $this->getBlobServiceClient()->getContainerClient($containerName);
        $containerClient->create();
        $this->assertTrue($containerClient->exists());

        // create the blob
        $contents = random_bytes(random_int(100, 1000));
        $contentType = 'text/plain';
        $blobClient = $containerClient->getBlobClient($blobName);
        $blobClient->upload($contents, new UploadBlobOptions($contentType));
        $this->assertTrue($blobClient->exists());

        // create server request and parsed arguments
        $queryParams = ['op' => 'content'];
        $uri = sprintf('http://localhost/%s/%s?%s', $containerName, $blobName, http_build_query($queryParams));
        $request = (new ServerRequest('GET', $uri))->withQueryParams($queryParams);
        $args = ['container' => $containerName, 'blob' => $blobName];

        /** @var GetBlobHandler $handler */
        $handler = $this->getContainer()->get(GetBlobHandler::class);

        // invoke handler and hold the response
        $response = $handler($request, new Response(), $args);

        // assert the expected response status code
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($contentType, $response->getHeader('content-type')[0]);
        $this->assertSame($contents, $response->getBody()->getContents());

        // delete the container
        $containerClient->delete();
    }

    public function testGetBlobProperties(): void
    {
        // generate random container and blob names
        $containerName = uniqid('test-');
        $blobName = sprintf('%s/%s.txt', uniqid(), uniqid());

        // create the container
        $containerClient = $this->getBlobServiceClient()->getContainerClient($containerName);
        $containerClient->create();
        $this->assertTrue($containerClient->exists());

        // create the blob
        $contents = random_bytes(random_int(100, 1000));
        $contentType = 'text/plain';
        $blobClient = $containerClient->getBlobClient($blobName);
        $blobClient->upload($contents, new UploadBlobOptions($contentType));
        $this->assertTrue($blobClient->exists());

        // set metadata
        $metadata = ['time' => time(), 'id' => uniqid()];
        $blobClient->setMetadata($metadata);

        // create server request and parsed arguments
        $queryParams = ['op' => 'props'];
        $uri = sprintf('http://localhost/%s/%s?%s', $containerName, $blobName, http_build_query($queryParams));
        $request = (new ServerRequest('GET', $uri))->withQueryParams($queryParams);
        $args = ['container' => $containerName, 'blob' => $blobName];

        /** @var GetBlobHandler $handler */
        $handler = $this->getContainer()->get(GetBlobHandler::class);

        // invoke handler and hold the response
        $response = $handler($request, new Response(), $args);

        // assert the expected response status code
        $this->assertSame(200, $response->getStatusCode());

        // decode and assert blob properties
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);
        $this->assertIsArray($data);
        $this->assertSame((string) $metadata['time'], $data['metadata']['time']);
        $this->assertSame($metadata['id'], $data['metadata']['id']);

        // delete the container
        $containerClient->delete();
    }

    public function testGetBlobTags(): void
    {
        // generate random container and blob names
        $containerName = uniqid('test-');
        $blobName = sprintf('%s/%s.txt', uniqid(), uniqid());

        // create the container
        $containerClient = $this->getBlobServiceClient()->getContainerClient($containerName);
        $containerClient->create();
        $this->assertTrue($containerClient->exists());

        // create the blob
        $contents = random_bytes(random_int(100, 1000));
        $contentType = 'text/plain';
        $blobClient = $containerClient->getBlobClient($blobName);
        $blobClient->upload($contents, new UploadBlobOptions($contentType));
        $this->assertTrue($blobClient->exists());

        // set tags
        $tags = ['tag1' => 'value1', 'tag2' => 'value2'];
        $blobClient->setTags($tags);

        // create server request and parsed arguments
        $queryParams = ['op' => 'tags'];
        $uri = sprintf('http://localhost/%s/%s?%s', $containerName, $blobName, http_build_query($queryParams));
        $request = (new ServerRequest('GET', $uri))->withQueryParams($queryParams);
        $args = ['container' => $containerName, 'blob' => $blobName];

        /** @var GetBlobHandler $handler */
        $handler = $this->getContainer()->get(GetBlobHandler::class);

        // invoke handler and hold the response
        $response = $handler($request, new Response(), $args);

        // assert the expected response status code
        $this->assertSame(200, $response->getStatusCode());

        // decode and assert blob tags
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);
        $this->assertIsArray($data);
        $this->assertSame($tags, $data);

        // delete the container
        $containerClient->delete();
    }

    public function testGetSasUrl(): void
    {
        // generate random container and blob names
        $containerName = uniqid('test-');
        $blobName = sprintf('%s/%s.txt', uniqid(), uniqid());

        // create the container
        $containerClient = $this->getBlobServiceClient()->getContainerClient($containerName);
        $containerClient->create();
        $this->assertTrue($containerClient->exists());

        // create the blob
        $contents = random_bytes(random_int(100, 1000));
        $contentType = 'text/plain';
        $blobClient = $containerClient->getBlobClient($blobName);
        $blobClient->upload($contents, new UploadBlobOptions($contentType));
        $this->assertTrue($blobClient->exists());

        // create server request and parsed arguments
        $queryParams = ['op' => 'sas', 'ttl' => 600, 'perms' => 'r'];
        $uri = sprintf('http://localhost/%s/%s?%s', $containerName, $blobName, http_build_query($queryParams));
        $request = (new ServerRequest('GET', $uri))->withQueryParams($queryParams);
        $args = ['container' => $containerName, 'blob' => $blobName];

        /** @var GetBlobHandler $handler */
        $handler = $this->getContainer()->get(GetBlobHandler::class);

        // invoke handler and hold the response
        $response = $handler($request, new Response(), $args);

        // assert the expected response status code
        $this->assertSame(200, $response->getStatusCode());

        // assert response body
        $body = $response->getBody()->getContents();
        $sas = new Uri($body);
        $this->assertNotFalse(strpos($sas->getPath(), $containerName));
        $this->assertNotFalse(strpos($sas->getPath(), $blobName));

        // delete the container
        $containerClient->delete();
    }
}
