<?php

declare(strict_types=1);

namespace App\Handler;

use AzureOss\Storage\Blob\BlobServiceClient;
use AzureOss\Storage\Blob\Models\BlobContainer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class GetStorageHandler
{
    public function __construct(
        private readonly BlobServiceClient $blobServiceClient

    ) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
$json = json_encode($this->listContainers());

$body = $response->getBody();
$body->write($json);

        return $response->withHeader('content-type', 'application/json')->withBody($body);
    }

    /**
     * @return BlobContainer[]
     */
    private function listContainers(): array
    {
        $containers = [];

        foreach ($this->blobServiceClient->getBlobContainers() as $container) {
            $containers[] = $container;
        }

        return $containers;
    }
}