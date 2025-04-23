<?php

declare(strict_types=1);

namespace App\Handler;

use App\Exception\InvalidBlobException;
use App\Exception\InvalidContainerException;
use App\Exception\InvalidOperationException;
use App\Exception\InvalidUploadException;
use App\Validator\BlobNameValidator;
use App\Validator\ContainerNameValidator;
use AzureOss\Storage\Blob\BlobServiceClient;
use AzureOss\Storage\Blob\Models\Blob;
use AzureOss\Storage\Blob\Models\GetBlobsOptions;
use AzureOss\Storage\Blob\Models\UploadBlobOptions;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

final class PostBlobHandler
{
    public function __construct(
        private readonly BlobServiceClient $blobServiceClient,
        private readonly ContainerNameValidator $containerNameValidator,
        private readonly BlobNameValidator $blobNameValidator
    ) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if (false === $this->containerNameValidator->validate($args['container'])->isValid()) {
            throw new InvalidContainerException($this->containerNameValidator->getError());
        }
        
        if (false === $this->blobNameValidator->validate($args['blob'])->isValid()) {
            throw new InvalidBlobException($this->blobNameValidator->getError());
        }

        switch ($request->getQueryParams()['op'] ?? '') {
            case 'upload':  
                return $this->uploadBlobs($request, $response, $args);
        }

        throw new InvalidOperationException();
   }

   private function uploadBlobs(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
   {
    $upload = current($request->getUploadedFiles());

    if (false === $upload instanceof UploadedFileInterface || UPLOAD_ERR_OK !== $upload->getError()) {
        throw new InvalidUploadException();
    }

$client = $this->blobServiceClient->getContainerClient($args['container'])->getBlobClient($args['blob']);
$client->upload($upload->getStream()->getContents(), new UploadBlobOptions($upload->getClientMediaType()));

return $response->withStatus(201);
   }
}