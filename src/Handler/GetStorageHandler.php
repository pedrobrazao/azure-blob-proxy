<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\InvalidOperationException;
use App\Exception\MissingRequiredArgumentException;
use App\Validator\RequiredArgumentValidator;
use AzureOss\Storage\Blob\BlobServiceClient;
use AzureOss\Storage\Blob\Models\BlobContainer;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class GetStorageHandler
{
    public function __construct(
        private BlobServiceClient $blobServiceClient,
        private RequiredArgumentValidator $requiredArgumentValidator
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return match ($request->getQueryParams()['op'] ?? null) {
            'list' => $this->listContainers($response),
            'find' => $this->findBlobsByTag($request, $response),
            default => throw new InvalidOperationException(),
        };
    }

    private function listContainers(ResponseInterface $response): ResponseInterface
    {
        $containers = [];

        foreach ($this->blobServiceClient->getBlobContainers() as $container) {
            $containers[$container->name] = $container;
        }

        $json = json_encode($containers);
        $body = Utils::streamFor($json);

        return $response->withStatus(200)->withHeader('content-type', 'application/json')->withBody($body);
    }

    /**
     * @see https://learn.microsoft.com/en-us/rest/api/storageservices/find-blobs-by-tags?tabs=microsoft-entra-id#remarks
     */
    private function findBlobsByTag(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (false === $this->requiredArgumentValidator->validate('where', $request->getQueryParams())->isValid()) {
            throw new MissingRequiredArgumentException($this->requiredArgumentValidator->getError());
        }

        $where = $request->getQueryParams()['where'];
        $blobs = [];

        foreach ($this->blobServiceClient->findBlobsByTag($where) as $blob) {
            $blobs[] = $blob;
        }

        $body = $response->getBody();
        $body->write(json_encode($blobs));

        return $response->withStatus(200)->withHeader('content-type', 'application/json')->withBody($body);
    }
}
