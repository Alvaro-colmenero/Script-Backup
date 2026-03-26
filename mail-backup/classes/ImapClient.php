<?php

class ImapClient {

    private $connection;
    private $mailbox;

    public function connect($email, $password, $host, $port) {
        $this->mailbox = "{{$host}:{$port}/imap/ssl/novalidate-cert}";

        $this->connection = imap_open($this->mailbox, $email, $password);

        if (!$this->connection) {
            die('Error IMAP: ' . imap_last_error());
        }
    }

    public function getFolders() {
        return imap_list($this->connection, $this->mailbox, "*");
    }

    public function openFolder($folder) {
        imap_reopen($this->connection, $folder);
    }

    public function getMessageCount() {
        return imap_num_msg($this->connection);
    }

    public function getHeaders($msgNumber) {
        return imap_fetchheader($this->connection, $msgNumber);
    }

    public function getBody($msgNumber) {
        return imap_body($this->connection, $msgNumber);
    }

    public function close() {
        imap_close($this->connection);
    }
}