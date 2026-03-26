<?php

class MailDownloader {
    private $imap;

    public function __construct($imapClient) {
        $this->imap = $imapClient;
    }

    public function downloadAll($folders, $basePath) {
        // 1. Calcular total global para el porcentaje
        $totalGlobal = 0;
        foreach ($folders as $folder) {
            $this->imap->openFolder($folder);
            $totalGlobal += $this->imap->getMessageCount();
        }

        if ($totalGlobal == 0) $totalGlobal = 1; // Evitar división por cero
        $processed = 0;

        // 2. Descargar
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

                // Actualizar sesión para el progreso real
                if ($processed % 5 == 0 || $processed == $totalGlobal) { // Actualizar cada 5 correos para no saturar
                    session_start();
                    $_SESSION['progress_percent'] = round(($processed / $totalGlobal) * 90); // Reservamos 10% para el ZIP
                    $_SESSION['progress_status'] = "Descargando: $processed de $totalGlobal correos...";
                    session_write_close();
                }
            }
        }
    }

    private function getMailboxPrefix() {
        $folders = $this->imap->getFolders();
        return strstr($folders[0], '}', true) . '}';
    }
}