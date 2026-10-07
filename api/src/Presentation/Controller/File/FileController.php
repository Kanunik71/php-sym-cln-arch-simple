<?php

declare(strict_types=1);

namespace App\Presentation\Controller\File;

use App\Application\Service\File\Action\FileUploadTmpService;
use App\Application\Service\File\Query\FileDownloadQueryService;
use App\Application\Service\File\Query\FileQueryService;
use App\Application\Exception\File\FileNotFoundException;
use App\Application\Exception\File\UnauthorizedFileAccessException;
use App\Presentation\Controller\BaseController;
use App\Shared\Utils\Http\ErrorResponseUtils;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/files', name: 'files_')]
#[IsGranted('ROLE_USER')]
final class FileController extends BaseController
{
    public function __construct(
        private readonly FileUploadTmpService $uploadTmpFile,
        private readonly FileQueryService $getFile,
        private readonly FileDownloadQueryService $downloadFile,
    ) {
    }

    #[Route('', name: 'upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $uploadedFile = $request->files->get('file');

        if (!$uploadedFile instanceof UploadedFile) {
            return ErrorResponseUtils::json(
                new InvalidArgumentException('Field "file" is required.'),
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            $contents = fopen($uploadedFile->getPathname(), 'rb');
            if ($contents === false) {
                throw new InvalidArgumentException('Unable to read uploaded file.');
            }

            $file = $this->uploadTmpFile->execute(
                userId: $this->getCurrentUserId(),
                originalName: $uploadedFile->getClientOriginalName(),
                mimeType: $this->resolveUploadedMimeType($uploadedFile),
                sizeBytes: $this->resolveUploadedSize($uploadedFile),
                contents: $contents,
            );
        } catch (InvalidArgumentException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_BAD_REQUEST);
        }

        return $this->json($file, Response::HTTP_CREATED);
    }

    private function resolveUploadedMimeType(UploadedFile $uploadedFile): string
    {
        $clientMime = $uploadedFile->getClientMimeType();
        if ($clientMime !== '') {
            return $clientMime;
        }

        $extension = strtolower(pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => throw new InvalidArgumentException(sprintf('Cannot detect mime type for file "%s".', $uploadedFile->getClientOriginalName())),
        };
    }

    private function resolveUploadedSize(UploadedFile $uploadedFile): int
    {
        $size = $uploadedFile->getSize();
        if ($size !== false && $size > 0) {
            return $size;
        }

        $pathSize = filesize($uploadedFile->getPathname());
        if ($pathSize === false || $pathSize <= 0) {
            throw new InvalidArgumentException('Uploaded file is empty.');
        }

        return $pathSize;
    }

    #[Route('/{id}', name: 'get', requirements: ['id' => '[0-9a-fA-F-]{36}'], methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        try {
            $file = $this->getFile->execute(
                userId: $this->getCurrentUserId(),
                fileId: $id,
            );
        } catch (FileNotFoundException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_NOT_FOUND);
        } catch (UnauthorizedFileAccessException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_FORBIDDEN);
        }

        return $this->json($file);
    }

    #[Route('/{id}/download', name: 'download', requirements: ['id' => '[0-9a-fA-F-]{36}'], methods: ['GET'])]
    public function download(string $id): Response
    {
        try {
            $download = $this->downloadFile->execute(
                userId: $this->getCurrentUserId(),
                fileId: $id,
            );
        } catch (FileNotFoundException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_NOT_FOUND);
        } catch (UnauthorizedFileAccessException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_FORBIDDEN);
        }

        $stream = $download->stream;

        return new StreamedResponse(
            function () use ($stream): void {
                fpassthru($stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            },
            Response::HTTP_OK,
            [
                'Content-Type' => $download->mimeType,
                'Content-Disposition' => sprintf('inline; filename="%s"', $download->originalName),
            ],
        );
    }
}
