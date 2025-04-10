<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\InvalidContainerException;
use App\Exception\InvalidOperationException;
use App\Validator\ContainerNameValidator;
use AzureOss\Storage\Blob\BlobServiceClient;
use AzureOss\Storage\Blob\Models\Blob;
use AzureOss\Storage\Blob\Models\GetBlobsOptions;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PutContainerHandler
{
    public function __construct(
        private readonly BlobServiceClient $blobServiceClient

    ) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $validator = new ContainerNameValidator($args['container'] ?? '');

        if (false === $validator->isValid()) {
            throw new InvalidContainerException;
        }

        switch ($request->getQueryParams()['op'] ?? '') {
            case 'create':  
                return $this->createContainer($response, $args);
                case 'metadata':
                    return $this->setMetadata($request, $response, $args);
        }

        throw new InvalidOperationException();
   }

   private function createContainer(ResponseInterface $response, array $args): ResponseInterface
   {
$this->blobServiceClient->getContainerClient($args['container'])->create();

return $response->withStatus(StatusCodeInterface::STATUS_CREATED);
   }

   private function setMetadata(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
   {
    $metadata = json_decode($request->getBody()->getContents(), true);
    $this->blobServiceClient->getContainerClient($args['container'])->setMetadata($metadata);

    return $response->withStatus(StatusCodeInterface::STATUS_OK);
   }
}