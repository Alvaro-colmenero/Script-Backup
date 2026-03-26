<?php

class MailDownloader {
    private $imap;

    public function __construct($imapClient) {
        $this->imap = $imapClient;
    }

    public function downloadAll($folders, $basePath) {
        $totalGlobal = 0;
        foreach ($folders as $folder) {
            $this->imap->openFolder($folder);
            $totalGlobal += $this->imap->getMessageCount();
        }

        $processed = 0;
        if ($totalGlobal == 0) $totalGlobal = 1;

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

                // Actualizamos sesión cada 2 correos para fluidez
                if ($processed % 2 == 0 || $processed == $totalGlobal) {
                    session_start();
                    $_SESSION['progress_percent'] = round(($processed / $totalGlobal) * 90);
                    $_SESSION['progress_status'] = "Descargando: $processed de $totalGlobal correos...";
                    session_write_close();
                }
            }
        }
    }

    private function getMailboxPrefix() {
        $folders = $this->imap->getFolders();
        return (isset($folders[0])) ? strstr($folders[0], '}', true) . '}' : '';
    }
}