<?php

declare(strict_types=1);

namespace App\Tests\Integration\Handler;

use App\Exception\InvalidBlobException;
use App\Exception\InvalidContainerException;
use App\Exception\InvalidOperationException;
use App\Handler\PostBlobHandler;
use App\Tests\Integration\IntegrationTestCase;
use AzureOss\Storage\Blob\Models\UploadBlobOptions;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\UploadedFile;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\Attributes\DataProvider;

final class PostBlobHandlerTest extends IntegrationTestCase
{
    /**
     * @param string[] $queryParams
     */
    #[DataProvider('argumentsProvider')]
    public function testInvalidArguments(string $containerName, string $blobName, array $queryParams, string $expectedException): void
    {
        $uri = sprintf('http://localhost/%s/%s', $containerName, $blobName);
        $request = (new ServerRequest('POST', $uri))->withQueryParams($queryParams);
        $response = new Response();
        $args = ['container' => $containerName, 'blob' => $blobName];

        /** @var PostBlobHandler $handler */
        $handler = $this->getContainer()->get(PostBlobHandler::class);
        ;

        $this->expectException($expectedException);
        $handler($request, $response, $args);
    }

    /**
     * @return array<array<string|string[]>>
     */
    public static function argumentsProvider(): array
    {
        return [
            ['-invalid-', 'foo/bar.txt', ['op' => 'upload'], InvalidContainerException::class],
            ['my-container', '', ['op' => 'upload'], InvalidBlobException::class],
            ['my-container', 'foo/bar.txt', ['op' => 'INVALID'], InvalidOperationException::class],
        ];
    }

    public function testUploadBlob(): void
    {
        // create container
        $containerName = uniqid('test-');
        $containerClient = $this->getBlobServiceClient()->getContainerClient($containerName);
        $containerClient->create();
        $this->assertTrue($containerClient->exists());

        // create uploaded file
        $contents = random_bytes(random_int(10, 20));
        $filename = uniqid() . '.txt';
        $contentType = 'text/plain';
        $uploadedFile = new UploadedFile(Utils::streamFor($contents), strlen($contents), 0, $filename, $contentType);

        // create server request and parsed arguments
        $blobName = uniqid() . '.txt';
        $queryParams = ['op' => 'upload'];
        $uri = sprintf('http://localhost/%s/%s?%s', $containerName, $blobName, http_build_query($queryParams));
        $request = (new ServerRequest('POST', $uri))->withQueryParams($queryParams)->withUploadedFiles([$uploadedFile]);
        $args = ['container' => $containerName, 'blob' => $blobName];

        /** @var PostBlobHandler $handler */
        $handler = $this->getContainer()->get(PostBlobHandler::class);

        // invoke handler and assert expected response status code
        $response = $handler($request, new Response(), $args);
        $this->assertSame(201, $response->getStatusCode());

        // assert that the blob has been created
        $blobClient = $containerClient->getBlobClient($blobName);
        $this->assertTrue($blobClient->exists());
        $blob = $blobClient->downloadStreaming();
        $this->assertSame($contents, $blob->content->getContents());
        $this->assertSame($contentType, $blob->properties->contentType);

        // delete the container
        $containerClient->delete();
    }
}
