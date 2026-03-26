<?php
$imap = imap_open('{imap.gmail.com:993/imap/ssl}INBOX', 'tu_usuario@gmail.com', 'tu_contraseña');
if($imap){
    echo "Conectado!";
    imap_close($imap);
}else{
    echo imap_last_error();
}