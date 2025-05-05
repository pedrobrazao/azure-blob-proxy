<?php

declare(strict_types=1);

namespace App\Tests\Integration\Handler;

use App\Exception\InvalidBlobException;
use App\Exception\InvalidContainerException;
use App\Exception\InvalidOperationException;
use App\Handler\PutBlobHandler;
use App\Tests\Integration\IntegrationTestCase;
use AzureOss\Storage\Blob\Models\UploadBlobOptions;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\Attributes\DataProvider;

final class PutBlobHandlerTest extends IntegrationTestCase
{
    #[DataProvider('argumentsProvider')]
    public function testInvalidArguments(string $containerName, string $blobName, array $queryParams, string $expectedException): void
    {
        $uri = sprintf('http://localhost/%s/%s', $containerName, $blobName);
        $request = (new ServerRequest('PUT', $uri))->withQueryParams($queryParams);
        $response = new Response();
        $args = ['container' => $containerName, 'blob' => $blobName];

        /** @var PutBlobHandler $handler */
        $handler = $this->getContainer()->get(PutBlobHandler::class);
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

    public function testCreateBlob(): void
    {
        // create container
        $containerName = uniqid('test-');
        $containerClient = $this->getBlobServiceClient()->getContainerClient($containerName);
        $containerClient->create();
        $this->assertTrue($containerClient->exists());

        // create server request and parsed arguments
        $blobName = uniqid() . '.txt';
        $queryParams = ['op' => 'create'];
        $uri = sprintf('http://localhost/%s/%s?%s', $containerName, $blobName, http_build_query($queryParams));
        $body = Utils::streamFor(random_bytes(random_int(10, 20)));
        $contentType = 'text/plain';
        $request = (new ServerRequest('PUT', $uri))->withQueryParams($queryParams)->withHeader('content-type', $contentType)->withBody($body);
        $args = ['container' => $containerName, 'blob' => $blobName];

        /** @var PutBlobHandler $handler */
        $handler = $this->getContainer()->get(PutBlobHandler::class);

        // invoke handler and assert expected response status code
        $response = $handler($request, new Response(), $args);
        $this->assertSame(201, $response->getStatusCode());
        $this->assertTrue($containerClient->getBlobClient($blobName)->exists());

        // delete the container
        $containerClient->delete();
    }

    public function testSetBlobMetadata(): void
    {
        // create container
        $containerName = uniqid('test-');
        $containerClient = $this->getBlobServiceClient()->getContainerClient($containerName);
        $containerClient->create();
        $this->assertTrue($containerClient->exists());

        // create blob
        $blobName = uniqid() . '.txt';
        $blobClient = $containerClient->getBlobClient($blobName);
        $blobClient->upload(random_bytes(random_int(10, 20)), new UploadBlobOptions('text/plain'));
        $this->assertTrue($blobClient->exists());

        // create server request and parsed arguments
        $queryParams = ['op' => 'metadata'];
        $uri = sprintf('http://localhost/%s/%s?%s', $containerName, $blobName, http_build_query($queryParams));
        $metadata = ['foo' => 'bar'];
        $body = Utils::streamFor(json_encode($metadata));
        $request = (new ServerRequest('PUT', $uri))->withQueryParams($queryParams)->withHeader('content-type', 'application/json')->withBody($body);
        $args = ['container' => $containerName, 'blob' => $blobName];

        /** @var PutBlobHandler $handler */
        $handler = $this->getContainer()->get(PutBlobHandler::class);

        // invoke handler and assert expected response status code
        $response = $handler($request, new Response(), $args);
        $this->assertSame(200, $response->getStatusCode());

        // assert metadata
        $this->assertSame($metadata, $blobClient->getProperties()->metadata);
        ;

        // delete the container
        $containerClient->delete();
    }
}
