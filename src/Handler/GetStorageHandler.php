<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\InvalidOperationException;
use App\Validator\RequiredArgumentValidator;
use AzureOss\Storage\Blob\BlobServiceClient;
use AzureOss\Storage\Blob\Models\BlobContainer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SebastianBergmann\CliParser\RequiredOptionArgumentMissingException;

final class GetStorageHandler
{
    public function __construct(
        private readonly BlobServiceClient $blobServiceClient

    ) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        switch ($request->getQueryParams()['op'] ?? null) {
            case 'list':
                return $this->listContainers();
        }

        throw new InvalidOperationException();
    }

    private function listContainers(ResponseInterface $response, array $args): ResponseInterface
    {
        $containers = [];

        foreach ($this->blobServiceClient->getBlobContainers() as $container) {
            $containers[] = $container;
        }

        $body = $response->getBody();
        $body->write(json_encode($containers));

        return $response->withStatus(StatusCodeInterface::STATUS_OK)->withHeader('content-type', 'application/json')->withBody($body);
    }

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/find-blobs-by-tags?tabs=microsoft-entra-id#remarks
     */
    private function findBlobsByTag(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $validator = new RequiredArgumentValidator('where', $request->getQueryParams());

        if (false === $validator->isValid()) {
            throw new RequiredOptionArgumentMissingException($validator->getError());
        }
        
        $where = $request->getQueryParams()['where'];
        $blobs = [];

        foreach ($this->blobServiceClient->findBlobsByTag($where) as $blob) {
            $blobs[] = $blob;
        }

        $body = $response->getBody();
        $body->write(json_encode($blobs));

        return $response->withStatus(StatusCodeInterface::STATUS_OK)->withHeader('content-type', 'application/json')->withBody($body);
    }
}