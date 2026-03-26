<?php

class MailDownloader {
    private $imap;
    private $progressFile;

    public function __construct($imapClient) {
        $this->imap = $imapClient;
    }

    public function setProgressFile($file) {
        $this->progressFile = $file;
    }

    private function updateRealProgress($percent, $status) {
        if ($this->progressFile) {
            file_put_contents($this->progressFile, json_encode(['percent' => $percent, 'status' => $status]));
        }
    }

    public function downloadAll($folders, $basePath) {
        $totalGlobal = 0;
        foreach ($folders as $folder) {
            $this->imap->openFolder($folder);
            $totalGlobal += $this->imap->getMessageCount();
        }

        $processed = 0;
        $totalGlobal = ($totalGlobal == 0) ? 1 : $totalGlobal;

        foreach ($folders as $folder) {
            $decodedFolder = imap_utf7_decode(str_replace($this->getMailboxPrefix(), '', $folder));
            $safeFolder = str_replace(['/', '\\'], '_', $decodedFolder);
            $folderPath = $basePath . '/' . $safeFolder;

            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0777, true);
            }

            $this->imap->openFolder($folder);
            $count = $this->imap->getMessageCount();

            for ($i = 1; $i <= $count; $i++) {
                $header = $this->imap->getHeaders($i);
                $body = $this->imap->getBody($i);
                file_put_contents($folderPath . "/msg_$i.eml", $header . "\n" . $body);

                $processed++;

                // Actualizamos el archivo de texto
                $percent = round(($processed / $totalGlobal) * 90);
                $this->updateRealProgress($percent, "Descargando: $processed de $totalGlobal correos...");
            }
        }
    }

    private function getMailboxPrefix() {
        $folders = $this->imap->getFolders();
        return (isset($folders[0])) ? strstr($folders[0], '}', true) . '}' : '';
    }
}