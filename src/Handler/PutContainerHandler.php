<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\InvalidContainerException;
use App\Exception\InvalidOperationException;
use App\Validator\ContainerNameValidator;
use AzureOss\Storage\Blob\BlobServiceClient;
use AzureOss\Storage\Blob\Models\Blob;
use AzureOss\Storage\Blob\Models\GetBlobsOptions;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class PutContainerHandler
{
    public function __construct(
        private BlobServiceClient $blobServiceClient,
        private ContainerNameValidator $containerNameValidator
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if (false === $this->containerNameValidator->validate($args['container'] ?? '')->isValid()) {
            throw new InvalidContainerException($this->containerNameValidator->getError());
        }
        return match ($request->getQueryParams()['op'] ?? '') {
            'create' => $this->createContainer($response, $args),
            'metadata' => $this->setMetadata($request, $response, $args),
            default => throw new InvalidOperationException(),
        };
    }

    private function createContainer(ResponseInterface $response, array $args): ResponseInterface
    {
        $client = $this->blobServiceClient->getContainerClient($args['container']);
        $client->create();

        return $response->withStatus(201);
    }

    private function setMetadata(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $metadata = json_decode($request->getBody()->getContents(), true);
        $client = $this->blobServiceClient->getContainerClient($args['container']);
        $client->setMetadata($metadata);

        return $response->withStatus(200);
    }
}
