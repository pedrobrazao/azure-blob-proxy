<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\InvalidContainerException;
use App\Validator\ContainerNameValidator;
use AzureOss\Storage\Blob\BlobServiceClient;
use AzureOss\Storage\Blob\Models\Blob;
use AzureOss\Storage\Blob\Models\GetBlobsOptions;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class GetContainerHandler
{
    public function __construct(
        private readonly BlobServiceClient $blobServiceClient

    ) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $query = $request->getQueryParams();
        $body = $response->getBody();

        $name = $args['container'] ?? '';
        $validator = new ContainerNameValidator($name);

        if (false === $validator->isValid()) {
            throw new InvalidContainerException;
        }

        $prefix = $query['prefix'] ?? null;
        $options = isset($query['pagesize']) ? new GetBlobsOptions((int) $query['pagesize']) : null;
        $json = json_encode($this->listBlobs($name, $prefix, $options));

        $body->write($json);

        return $response->withHeader('content-type', 'application/json')->withBody($body);
    }

    /**
     * @return Blob[]
     */
    private function listBlobs(string $name, string $prefix = null, ?GetBlobOptions $options = null): array
    {
        $blobs = [];
        $client = $this->blobServiceClient->getContainerClient($name);

        foreach ($client->getBlobs($prefix, $options) as $blob) {
            $blobs[] = $blob;
        }
        return $blobs;
    }
}