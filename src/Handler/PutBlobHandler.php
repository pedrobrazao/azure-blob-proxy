<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\InvalidBlobException;
use App\Exception\InvalidContainerException;
use App\Exception\InvalidOperationException;
use App\Validator\BlobNameValidator;
use App\Validator\ContainerNameValidator;
use AzureOss\Storage\Blob\BlobServiceClient;
use AzureOss\Storage\Blob\Models\Blob;
use AzureOss\Storage\Blob\Models\GetBlobsOptions;
use AzureOss\Storage\Blob\Models\UploadBlobOptions;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class PutBlobHandler
{
    public function __construct(
        private BlobServiceClient $blobServiceClient,
        private ContainerNameValidator $containerNameValidator,
        private BlobNameValidator $blobNameValidator
    ) {
    }

    /**
     * @param array<string, string> $args
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if (false === $this->containerNameValidator->validate($args['container'])->isValid()) {
            throw new InvalidContainerException($this->containerNameValidator->getError());
        }

        if (false === $this->blobNameValidator->validate($args['blob'])->isValid()) {
            throw new InvalidBlobException($this->blobNameValidator->getError());
        }
        return match ($request->getQueryParams()['op'] ?? null) {
            'create' => $this->createBlob($request, $response, $args),
            'metadata' => $this->setMetadata($request, $response, $args),
            default => throw new InvalidOperationException(),
        };
    }

    /**
     * @param array<string, string> $args
     */
    private function createBlob(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $header = $request->getHeader('content-type');
        $options = new UploadBlobOptions($header[0] ?? null);

        $client = $this->blobServiceClient->getContainerClient($args['container'])->getBlobClient($args['blob']);
        $client->upload($request->getBody()->getContents(), $options);

        return $response->withStatus(201);
    }

    /**
     * @param array<string, string> $args
     */
    private function setMetadata(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $metadata = json_decode($request->getBody()->getContents(), true);
        $this->blobServiceClient->getContainerClient($args['container'])->getBlobClient($args['blob'])->setMetadata($metadata);

        return $response->withStatus(200);
    }
}
