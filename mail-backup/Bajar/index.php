<?php
    require 'config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mail Backup Pro</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
<h2>Backup de Correo</h2>
<div class="form-container">
    <form id="backupForm" method="POST" action="iniciar.php" target="worker">

        <label for="email">Correo electrónico:</label>
        <input id="email" type="text" name="email" placeholder="ej: info@dominio.com" required>

        <label for="password">Contraseña:</label>
        <input id="password" type="password" name="password" placeholder="••••••••" required>

        <label for="imap">Servidor IMAP:</label>
        <input id="imap" type="text" name="imap" placeholder="imap.tuservidor.com" required>

        <label for="port">Puerto:</label>
        <input id="port" type="number" name="port" value="993">

        <button type="submit" id="btnSubmit">
            Iniciar Backup
        </button>
    </form>

    <div class="progress-container" id="progressContainer">
        <div id="progressText">Iniciando...</div>
        <div class="progress-bar" id="progressBar"></div>
    </div>

    <div id="resultArea" style="margin-top:20px;"></div>
</div>

<iframe name="worker" style="display:none;"></iframe>

<script>
    let emailStr, now;

    function toggleCpanelField() {
        const isCpanel = document.getElementById('auth_type').value === 'cpanel';
        const cpanelDiv = document.getElementById('cpanel_user_div');
        cpanelDiv.style.display = isCpanel ? 'block' : 'none';
        document.getElementById('cpanel_user').required = isCpanel;
    }

    const form = document.getElementById('backupForm');
    let interval;

    form.addEventListener('submit', function() {
        document.getElementById('progressContainer').style.display = 'block';
        document.getElementById('btnSubmit').disabled = true;
        document.getElementById('resultArea').innerHTML = "";

        emailStr = document.getElementById('email').value;
        now = new Date();

        if(interval) clearInterval(interval);

        interval = setInterval(() => {
            fetch('progress.php?t=' + Date.now())
                .then(res => res.json())
                .then(data => {
                    document.getElementById('progressBar').style.width = data.percent + '%';

                    if (data.status.startsWith('Error')) {
                        document.getElementById('progressContainer').style.display = 'none';
                        let error = data.status,
                            fin = error.lastIndexOf(':');
                        if (fin === error.indexOf(':')) fin = null;

                        error = error.substring(
                            error.indexOf(':') + 1,
                            fin ?? error.length
                        ).trim();

                        //Se procesan los errores
                        switch(true){
                            case data.status.includes('Too many login failures'):
                                error = "Correo y/o contraseña incorrectos."
                                break;

                            case data.status.includes("Timed out"):
                                error = "Servidor de correo no responde.";
                                break;

                            case data.status.includes("Host not found (#11001)"):
                                error = "Servidor no existe";
                                break;

                            default:
                                console.error(data.status);
                        }

                        document.getElementById('resultArea').innerHTML =
                          "<p class='error-panel'>" +
                               error
                        + "</p>";
                        clearInterval(interval);
                        document.getElementById('btnSubmit').disabled = false;
                    } else {
                        document.getElementById('progressText')
                                .textContent = data.status + ' (' + data.percent + '%)';
                    }

                    if(data.percent >= 100) {
                        clearInterval(interval);
                        document.getElementById('btnSubmit').disabled = false;

                        document.getElementById('resultArea').innerHTML =
                            "<br><a href='backups/" + zipFile() + "' "
                                + "style='padding:15px; background:#28a745; color:white; text-decoration:none; "
                                + "border-radius:5px; display:inline-block;'>"
                                    + "DESCARGAR BACKUP ZIP"
                            + "</a>";
                    }
                })
                .catch(err => {
                    console.error("Error:", err)
                });
        }, 1000);
    });

    function zipFile ()
    {
        let y = now.getFullYear(), m = (now.getMonth() + 1).toString().padStart(2, '0'),
            d = (now.getDate()).toString().padStart(2, '0'), h = (now.getHours() - 1).toString().padStart(2, '0'),
            i = now.getMinutes().toString().padStart(2, '0'), s = now.getSeconds().toString().padStart(2, '0'),
            timeStamp = y + '-' + m + '-' + d + '_' + h + '-' + i + '-' + s,
            path = '<?= BACKUP_PATH ?>'
                + emailStr.replaceAll(new RegExp('[^a-zA-Z0-9]', 'g'), '_')
                + '_' + timeStamp + '.zip';

        return path.substring(path.lastIndexOf('/') + 1);
    }
</script>
</body>
</html>