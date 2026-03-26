<?php
//phpinfo();

$imap = imap_open(
    '{imap.gmail.com:993/imap/ssl/novalidate-cert}INBOX',
    '',
    '' //Contraseña de APLICACION
);

if ($imap) {
    echo "Conectado!";
    imap_close($imap);

} else {
    echo "No se pudo conectarse al correo.";
    echo '</br>';
    echo imap_last_error();

}