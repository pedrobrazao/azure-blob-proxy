<?php

declare(strict_types=1);

namespace App\Handler;

use AzureOss\Storage\Blob\BlobServiceClient;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class GetBlobHandler
{
    public function __construct(
        private BlobServiceClient $blobServiceClient

    ) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $response;
    }
}