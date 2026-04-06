<?php
    require 'config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mail Backup Pro</title>
    <link rel="stylesheet" href="./css/styles.css">
    <style>
        .hidden { display: none; }
        .progress-container { width: 100%; background: #eee; border-radius: 8px; margin-top: 20px; height: 30px; position: relative; overflow: hidden; display: none; border: 1px solid #ddd; }
        .progress-bar { width: 0; height: 100%; background: #28a745; transition: width 0.3s ease; }
        #progressText { position: absolute; width: 100%; text-align: center; top: 5px; font-weight: bold; color: #333; z-index: 1; }

        /* Estilos para etiquetas y campos */
        .form-container label {
            display: block;
            margin-top: 12px; /* Espaciado entre campos */
            font-weight: bold;
            font-size: 0.9em;
            color: #555;
            text-align: left;
        }

        /* --- CAMBIO AQUÍ: Quita el espacio superior de la primera etiqueta --- */
        .form-container label:first-of-type {
            margin-top: 0;
        }

        select, input { width: 100%; padding: 10px; margin: 5px 0 10px 0; border-radius: 5px; border: 1px solid #ccc; box-sizing: border-box; }

        /*.btn-download { display: inline-block; margin-top: 10px; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }*/
        #btnSubmit { background-color: #007bff; color: white; border: none; cursor: pointer; font-weight: bold; margin-top: 15px; width: 100%; padding: 12px; }
        #btnSubmit:hover { background-color: #0056b3; }
        #btnSubmit:disabled { background-color: #ccc; cursor: not-allowed; }
        select, input { width: 100%; padding: 10px; margin: 8px 0; border-radius: 5px; border: 1px solid #ccc; box-sizing: border-box; }
        p.error-panel { display: inline-block; background-color: lightcoral; border: 1px solid red;padding: 5px; border-radius: 3px; }
    </style>
</head>
<body>
<div class="form-container">
    <h2>Backup de Correo</h2>
    <form id="backupForm" method="POST" action="iniciar.php" target="worker">

        <label for="email">Correo electrónico:</label>
        <input id="email" type="text" name="email" placeholder="ej: info@dominio.com" required>

        <div id="cpanel_user_div" class="hidden">
            <label for="cpanel_user">Usuario de cPanel:</label>
            <input type="text" name="cpanel_user" id="cpanel_user" placeholder="Tu usuario de hosting">
        </div>

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
        now = new Date(Date.now() + (new Date()).getTimezoneOffset() * 60000);

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
            d = (now.getDate()).toString().padStart(2, '0'), h = now.getHours().toString().padStart(2, '0'),
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