<?php

class MailDownloader {

    private $imap;

    public function __construct($imapClient) {
        $this->imap = $imapClient;
    }

    public function downloadAll($folders, $basePath) {

        foreach ($folders as $folder) {

            $decodedFolder = imap_utf7_decode(str_replace($this->getMailboxPrefix(), '', $folder));
            $safeFolder = str_replace(['/', '\\'], '_', $decodedFolder);

            $folderPath = $basePath . '/' . $safeFolder;

            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0777, true);
            }

            echo "Procesando carpeta: $decodedFolder<br>";

            $this->imap->openFolder($folder);

            $messageCount = $this->imap->getMessageCount();

            for ($i = 1; $i <= $messageCount; $i++) {

                $header = $this->imap->getHeaders($i);
                $body = $this->imap->getBody($i);

                $emailContent = $header . "\n" . $body;

                file_put_contents($folderPath . "/msg_$i.eml", $emailContent);
            }
        }
    }

    private function getMailboxPrefix() {
        return strstr($this->imap->getFolders()[0], '}', true) . '}';
    }
}