<?php

namespace classes;
class MailDownloader
{

    private $imap;
    private $progressFile;

    public function __construct($imapClient)
    {
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
    public function updateRealProgress($percent, $status): void
    {
        if ($this->progressFile) {
            file_put_contents($this->progressFile, json_encode([
                'percent' => $percent,
                'status' => $status
            ]));
        }
    }

    public function uploadAll($email): void
    {

    }

    public function downloadAll($folders, $basePath): void
    {
        // Límite de correos por carpeta, si es 0 los coge todos
        $limit = 0;
        $totalGlobal = 0;

        // 1. Contar mensajes totales para el cálculo del porcentaje
        $this->updateRealProgress(0, "Contando mensajes... ");
        foreach ($folders as $folder) {
            $this->imap->openFolder($folder);
            $count = $this->imap->getMessageCount();
            if (!$limit || $count < $limit) $totalGlobal += $count;
            else $totalGlobal += $limit;
        }

        if (!$totalGlobal) $totalGlobal = 1;
        $processed = 0;
        $percent = 0;

        // 2. Proceso de descarga
        foreach ($folders as $folder) {
            $decodedFolder = imap_utf7_decode(str_replace($this->getMailboxPrefix(), '', $folder));
            $safeFolder = str_replace(['/', '\\'], '_', $decodedFolder);
            $folderPath = $basePath . '/' . $safeFolder;

            $this->updateRealProgress($percent, "Creando carpeta $safeFolder... ");
            if (!file_exists($folderPath)) mkdir($folderPath, 0777, true);


            $this->imap->openFolder($folder);
            $messageCount = $this->imap->getMessageCount();

            // Calculamos desde dónde empezar para obtener los últimos 500
            // IMAP cuenta de 1 a N, siendo N el más reciente.
            if (!$limit || $limit > $messageCount) $start = 1;
            else $start = $messageCount - $limit + 1;

            // Recorremos desde el más reciente hacia atrás
            for ($i = $messageCount; $i >= $start; $i--) {

                $header = $this->imap->getHeaders($i);
                $body = $this->imap->getBody($i);
                $emailContent = $header . "\n" . $body;

                // Guardamos el archivo .eml
                file_put_contents($folderPath . "/msg_$i.eml", $emailContent);

                $processed++;

                // Actualizamos el progreso en el archivo .txt (reservamos el 10% final para el ZIP)
                $percent = round(($processed / $totalGlobal) * 90);
                $this->updateRealProgress($percent, "Descargando: $processed de $totalGlobal correos...");
            }
        }
    }

    private function getMailboxPrefix()
    {
        $folders = $this->imap->getFolders();
        if (isset($folders[0])) {
            return strstr($folders[0], '}', true) . '}';
        }
        return '';
    }
}