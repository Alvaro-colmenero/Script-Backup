<?php require 'config.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Email Backup Pro | Descargar Correos</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f0f2f5; display: flex; justify-content: center; padding: 40px; }
        .container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); width: 100%; max-width: 450px; text-align: center; }
        h2 { color: #1a73e8; margin-bottom: 20px; }
        label { display: block; text-align: left; margin-top: 15px; font-weight: bold; font-size: 0.9rem; color: #555; }
        input { width: 100%; padding: 12px; margin-top: 5px; border-radius: 8px; border: 1px solid #ddd; box-sizing: border-box; }
        #btnStart { background: #007bff; color: white; border: none; padding: 15px; width: 100%; border-radius: 8px; font-weight: bold; cursor: pointer; margin-top: 20px; }
        #btnStart:disabled { background: #ccc; }
        .progress-container { width: 100%; background: #eee; border-radius: 20px; margin-top: 25px; height: 25px; position: relative; overflow: hidden; display: none; }
        .progress-bar { width: 0%; height: 100%; background: #007bff; transition: width 0.3s; }
        #progressText { position: absolute; width: 100%; text-align: center; top: 4px; font-size: 0.8rem; font-weight: bold; }
        #resultArea { margin-top: 20px; }
        .download-btn { display: inline-block; padding: 12px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h2>Email Backup Tool</h2>
    <form id="backupForm" action="procesar_backup.php" method="POST" target="worker">
        <label>Correo Electrónico:</label>
        <input type="email" name="email" required placeholder="ejemplo@gmail.com">

        <label>Contraseña de Aplicación:</label>
        <input type="password" name="password" required placeholder="•••• •••• •••• ••••">

        <label>Servidor IMAP:</label>
        <input type="text" name="imap" value="imap.gmail.com" required>

        <label>Límite de correos (vacío = todos):</label>
        <input type="number" name="limite" placeholder="Ej: 100">

        <button type="submit" id="btnStart">INICIAR DESCARGA</button>
    </form>

    <div class="progress-container" id="progCont">
        <div id="progressText">Conectando...</div>
        <div class="progress-bar" id="progBar"></div>
    </div>

    <div id="resultArea"></div>
</div>

<iframe name="worker" style="display:none;"></iframe>

<script>
    const form = document.getElementById('backupForm');
    const btn = document.getElementById('btnStart');
    let interval;

    form.addEventListener('submit', () => {
        btn.disabled = true;
        document.getElementById('progCont').style.display = 'block';
        document.getElementById('resultArea').innerHTML = "";

        interval = setInterval(() => {
            fetch('progress.php?t=' + Date.now())
                .then(r => r.json())
                .then(data => {
                    document.getElementById('progBar').style.width = data.percent + '%';
                    document.getElementById('progressText').textContent = data.status + ' (' + data.percent + '%)';

                    if (data.percent >= 100) {
                        clearInterval(interval);
                        btn.disabled = false;
                        if(data.file) {
                            document.getElementById('resultArea').innerHTML =
                                `<a href="${data.file}" class="download-btn" download>📥 DESCARGAR BACKUP .ZIP</a>`;
                        }
                    }
                    if (data.status.includes('Error')) {
                        clearInterval(interval);
                        btn.disabled = false;
                        document.getElementById('resultArea').innerHTML = `<p style="color:red">${data.status}</p>`;
                    }
                });
        }, 1000);
    });
</script>
</body>
</html>