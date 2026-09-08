<?php
namespace Xdecaro\Component\Decarodocuments\Administrator\Service;

defined('_JEXEC') or die;

use RuntimeException;

/**
 * Private file storage for Documents.
 *
 * Files are stored one directory above JPATH_ROOT by default and are never
 * addressed by their original filename. Consumers only receive document IDs.
 */
final class StorageService
{
    public const MAX_FILE_SIZE = 26214400; // 25 MiB

    private const ALLOWED = [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'txt' => ['text/plain'],
        'csv' => ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'odt' => ['application/vnd.oasis.opendocument.text', 'application/zip'],
        'ods' => ['application/vnd.oasis.opendocument.spreadsheet', 'application/zip'],
    ];

    public function storeUploadedFile(array $file, string $uuid): array
    {
        $this->validateUploadShape($file);

        $tmp = (string) $file['tmp_name'];
        $originalName = $this->normaliseOriginalName((string) $file['name']);
        $size = (int) $file['size'];
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));

        if ($size <= 0 || $size > self::MAX_FILE_SIZE) {
            throw new RuntimeException('The uploaded file exceeds the permitted size or is empty.');
        }

        if (!isset(self::ALLOWED[$extension])) {
            throw new RuntimeException('This file extension is not permitted.');
        }

        if (!is_uploaded_file($tmp)) {
            throw new RuntimeException('The uploaded file could not be verified by PHP.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = strtolower(trim((string) $finfo->file($tmp)));

        if ($mime === '' || !in_array($mime, self::ALLOWED[$extension], true)) {
            throw new RuntimeException('The server-detected MIME type does not match an allowed file type.');
        }

        $directory = $this->ensureStorageDirectory();
        $storedName = strtolower($uuid) . '.blob';
        $destination = $directory . DIRECTORY_SEPARATOR . $storedName;

        if (file_exists($destination)) {
            throw new RuntimeException('A document with the generated storage identifier already exists.');
        }

        if (!move_uploaded_file($tmp, $destination)) {
            throw new RuntimeException('The uploaded file could not be moved into private storage.');
        }

        @chmod($destination, 0600);

        $hash = hash_file('sha256', $destination);
        if (!is_string($hash) || strlen($hash) !== 64) {
            @unlink($destination);
            throw new RuntimeException('The document integrity hash could not be calculated.');
        }

        return [
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'mime_type' => $mime,
            'file_size' => $size,
            'sha256' => $hash,
        ];
    }

    public function resolve(string $storedName): string
    {
        if (!preg_match('/^[a-f0-9-]{36}\.blob$/D', $storedName)) {
            throw new RuntimeException('Invalid document storage identifier.');
        }

        $directory = $this->ensureStorageDirectory();
        $base = realpath($directory);
        $path = realpath($directory . DIRECTORY_SEPARATOR . $storedName);

        if ($base === false || $path === false || !is_file($path)) {
            throw new RuntimeException('The stored document file is not available.');
        }

        $prefix = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strncmp($path, $prefix, strlen($prefix)) !== 0) {
            throw new RuntimeException('The document storage path is invalid.');
        }

        return $path;
    }

    public function delete(string $storedName): void
    {
        if ($storedName === '') {
            return;
        }

        try {
            $path = $this->resolve($storedName);
        } catch (RuntimeException) {
            return;
        }

        if (!@unlink($path) && is_file($path)) {
            throw new RuntimeException('The stored document file could not be removed.');
        }
    }

    public function isReady(): bool
    {
        try {
            $directory = $this->ensureStorageDirectory();
            return is_dir($directory) && is_writable($directory);
        } catch (RuntimeException) {
            return false;
        }
    }

    private function ensureStorageDirectory(): string
    {
        $parent = dirname(JPATH_ROOT);
        $siteKey = substr(hash('sha256', (string) realpath(JPATH_ROOT)), 0, 20);
        $directory = $parent . DIRECTORY_SEPARATOR . 'xdecaro-documents' . DIRECTORY_SEPARATOR . $siteKey;

        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException(
                'Documents private storage cannot be created outside the Joomla public root.'
            );
        }

        @chmod($directory, 0700);

        if (!is_writable($directory)) {
            throw new RuntimeException('Documents private storage is not writable.');
        }

        return $directory;
    }

    private function validateUploadShape(array $file): void
    {
        foreach (['name', 'tmp_name', 'size', 'error'] as $key) {
            if (!array_key_exists($key, $file)) {
                throw new RuntimeException('The uploaded file payload is incomplete.');
            }
        }

        $error = (int) $file['error'];
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('The upload did not complete successfully (code ' . $error . ').');
        }
    }

    private function normaliseOriginalName(string $name): string
    {
        $name = trim(str_replace(["\0", '/', '\\'], '', $name));
        $name = preg_replace('/[\x00-\x1F\x7F]+/u', '', $name) ?: '';

        if ($name === '' || strlen($name) > 255) {
            throw new RuntimeException('The original filename is invalid.');
        }

        return $name;
    }
}
