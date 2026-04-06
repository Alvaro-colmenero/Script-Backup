<?php

class MailUploader
{
    private $imap;
    private $progressFile;

    public function __construct($imapClient) {
        $this->imap = $imapClient;
    }

    /**
     * Establece el archivo donde se escribirá el progreso para la barra real
     */
    public function setProgressFile($file): void
    {
        $this->progressFile = $file;
    }

    /**
     * Función interna para actualizar el archivo .txt de progreso
     */
    public function updateProgress($percent, $status): void
    {
        if ($this->progressFile) {
            file_put_contents($this->progressFile, json_encode([
                'percent' => $percent,
                'status' => $status
            ]));
        }
    }

    public function uploadAll($folders, $basePath): void
    {

    }
}