<?php

declare(strict_types=1);

namespace App\File;

use RuntimeException;

enum FileMode: string
{
    case READ = 'rb';
    case WRITE = 'wb';
    case APPEND = 'ab';
}

abstract class AbstractFile
{
    /**
     * @var resource
     */
    protected $handle;

    public function __construct(string $filePath,FileMode $mode)
    {
        $this->openStream($filePath, $mode);
    }

    protected function openStream(string $filePath,FileMode $mode): void
    {
        if (FileMode::READ === $mode) {
            if (!file_exists($filePath)) {
                throw new RuntimeException("File '$filePath' does not exist.");
            }
        }

        $tmpFileHandle = fopen($filePath, $mode->value);
        if (false === $tmpFileHandle) {
            throw new RuntimeException("Could not open file '$filePath'.");
        }

        $this->handle = $tmpFileHandle;
    }

    public function __destruct()
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
    }
}
