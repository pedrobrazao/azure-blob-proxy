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
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PutBlobHandler
{
    public function __construct(
        private readonly BlobServiceClient $blobServiceClient

    ) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $containerVValidator = new ContainerNameValidator($args['container'] ?? '');
        if (false === $containerVValidator->isValid()) {
            throw new InvalidContainerException($containerVValidator->getError());
        }
        
        $blobValidator = new BlobNameValidator($args['blob'] ?? '');
        if (false === $blobValidator->isValid()) {
            throw new InvalidBlobException($blobValidator->getError());
        }

        switch ($request->getQueryParams()['op'] ?? '') {
            case 'create':  
                return $this->createBlob($request, $response, $args);
                case 'metadata':
                    return $this->setMetadata($request, $response, $args);
        }

        throw new InvalidOperationException();
   }

   private function createBlob(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
   {
    $header = $request->getHeader('content-type');
    $options = new UploadBlobOptions($header[0] ?? null);

$client = $this->blobServiceClient->getContainerClient($args['container'])->getBlobClient($args['blob']);
$client->upload($request->getBody()->getContents(), $options);

return $response->withStatus(StatusCodeInterface::STATUS_CREATED);
   }

   private function setMetadata(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
   {
    $metadata = json_decode($request->getBody()->getContents(), true);
    $this->blobServiceClient->getContainerClient($args['container'])->getBlobClient($args['blob'])->setMetadata($metadata);

    return $response->withStatus(StatusCodeInterface::STATUS_OK);
   }
}