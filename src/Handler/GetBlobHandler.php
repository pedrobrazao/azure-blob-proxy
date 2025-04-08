<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\InvalidBlobException;
use App\Exception\InvalidContainerException;
use App\Validator\BlobNameValidator;
use App\Validator\ContainerNameValidator;
use AzureOss\Storage\Blob\BlobServiceClient;
use AzureOss\Storage\Blob\Models\BlobDownloadStreamingResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class GetBlobHandler
{
    public function __construct(
        private readonly BlobServiceClient $blobServiceClient

    ) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $query = $request->getQueryParams();
        $body = $response->getBody();

        $containerValidator = new ContainerNameValidator($args['container'] ?? '');
        if (false === $containerVValidator->isValid()) {
            throw new InvalidContainerException($containerValidator->getError());
        }
        
        $blobValidator = new BlobNameValidator($args['blob'] ?? '');
        if (false === $blobValidator->isValid()) {
            throw new InvalidBlobException($blobValidator->getError());
        }

        switch ($query['op'] ?? 'content') {
            default:
            $stream = $this->getDownloadStream($args['container'], $args['blob']);
            $response = $response->withHeader('content-type', $stream->properties->contentType);
            $body->write($stream->content);
            break;
        }

        return $response->withBody($body);
    }

    private function getDownloadStream(string $containerName, string $blobName): BlobDownloadStreamingResult
    {
        $client = $this->blobServiceClient->getContainerClient($containerName)->getBlobClient($blobName);
        
        return $client->downloadStreaming();
}