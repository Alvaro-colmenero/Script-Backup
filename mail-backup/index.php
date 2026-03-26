<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mail Backup Pro</title>
    <link rel="stylesheet" href="./css/styles.css">
    <style>
        .hidden { display: none; }
        .progress-container { width: 100%; background: #eee; border-radius: 8px; margin-top: 20px; height: 30px; position: relative; overflow: hidden; display: none; }
        .progress-bar { width: 0%; height: 100%; background: #28a745; transition: width 0.4s ease; }
        #progressText { position: absolute; width: 100%; text-align: center; top: 5px; font-weight: bold; color: #333; z-index: 1; }
        select, input { width: 100%; padding: 10px; margin: 8px 0; border-radius: 5px; border: 1px solid #ccc; box-sizing: border-box; }
    </style>
</head>
<body>
<div class="form-container">
    <h2>Backup de Correo</h2>
    <form id="backupForm" method="POST" action="backup.php" target="worker">
        <label>Método de conexión:</label>
        <select name="auth_type" id="auth_type" onchange="toggleCpanelField()">
            <option value="direct">Correo + Contraseña Individual</option>
            <option value="cpanel">Acceso Maestro (cPanel)</option>
        </select>

        <input type="text" name="email" placeholder="Correo a respaldar" required>

        <div id="cpanel_user_div" class="hidden">
            <input type="text" name="cpanel_user" id="cpanel_user" placeholder="Usuario de tu cPanel">
        </div>

        <input type="password" name="password" placeholder="Contraseña" required>
        <input type="text" name="imap" placeholder="imap.tuservidor.com" required>
        <input type="number" name="port" value="993">

        <button type="submit" id="btnSubmit">Iniciar Respaldo Real</button>
    </form>

    <div class="progress-container" id="progressContainer">
        <div id="progressText">Esperando servidor...</div>
        <div class="progress-bar" id="progressBar"></div>
    </div>

    <div id="resultArea" style="margin-top:20px;"></div>
</div>

<iframe name="worker" style="display:none;"></iframe>

<script>
    function toggleCpanelField() {
        const isCpanel = document.getElementById('auth_type').value === 'cpanel';
        document.getElementById('cpanel_user_div').classList.toggle('hidden', !isCpanel);
    }

    const form = document.getElementById('backupForm');
    form.addEventListener('submit', function() {
        document.getElementById('progressContainer').style.display = 'block';
        document.getElementById('btnSubmit').disabled = true;

        // Consultar progreso cada segundo
        const interval = setInterval(() => {
            fetch('progress.php')
                .then(res => res.json())
                .then(data => {
                    document.getElementById('progressBar').style.width = data.percent + '%';
                    document.getElementById('progressText').textContent = data.status + ' (' + data.percent + '%)';

                    if(data.percent >= 100) {
                        clearInterval(interval);
                        document.getElementById('btnSubmit').disabled = false;
                    }
                });
        }, 1000);
    });
</script>
</body>
</html>