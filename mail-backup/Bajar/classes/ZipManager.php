<?php

namespace classes;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class ZipManager
{

    public function createZip($source, $destination)
    {

        $zip = new ZipArchive();

        if (!$zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE))
            return false;

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($source) + 1);

                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();
        return true;
    }
}