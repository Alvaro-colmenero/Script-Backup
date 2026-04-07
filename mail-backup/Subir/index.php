<?php require 'config.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mail Restore Pro | Restauración IMAP</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>

<h2>Mail Restore Pro</h2>

<div class="form-container">
    <form id="restoreForm" method="POST" action="procesar_restauracion.php" enctype="multipart/form-data" target="worker">

        <label for="zip_file">Archivo ZIP (.zip):</label>
        <input type="file" name="zip_file" id="zip_file" accept=".zip" required>

        <label for="email">Correo electrónico destino:</label>
        <input type="text" name="email" id="email" placeholder="ej: info@tu-dominio.com" required>

        <label for="password">Contraseña (App Password):</label>
        <input type="password" name="password" id="password" placeholder="••••••••" required>

        <label for="imap">Servidor IMAP:</label>
        <input type="text" name="imap" id="imap" value="imap.gmail.com" required>

        <button type="submit" id="btnSubmit">
            Iniciar Restauración
        </button>
    </form>

    <div class="progress-container" id="progressContainer">
        <div id="progressText">Preparando...</div>
        <div class="progress-bar" id="progressBar"></div>
    </div>

    <div id="resultArea"></div>
</div>

<iframe name="worker" id="worker" style="display:none;"></iframe>

<script>
    const form = document.getElementById('restoreForm');
    const btn = document.getElementById('btnSubmit');
    const progContainer = document.getElementById('progressContainer');
    const progBar = document.getElementById('progressBar');
    const progText = document.getElementById('progressText');
    const resultArea = document.getElementById('resultArea');

    let interval;

    form.addEventListener('submit', function() {
        progContainer.style.display = 'block';
        btn.disabled = true;
        resultArea.innerHTML = "";
        progBar.style.width = '0%';
        progText.textContent = "Iniciando subida...";

        if(interval) clearInterval(interval);

        interval = setInterval(() => {
            fetch('progress.php?t=' + Date.now())
                .then(res => res.json())
                .then(data => {
                    progBar.style.width = data.percent + '%';
                    progText.textContent = data.status + ' (' + data.percent + '%)';

                    if (data.status.toLowerCase().includes('error')) {
                        resultArea.innerHTML = `<p class="error-panel">${data.status}</p>`;
                        clearInterval(interval);
                        btn.disabled = false;
                    }

                    if(data.percent >= 100) {
                        clearInterval(interval);
                        btn.disabled = false;
                        resultArea.innerHTML = "<p class='success-msg'>¡Proceso completado con éxito!</p>";
                    }
                })
                .catch(err => console.error("Error consultando progreso:", err));
        }, 1000);
    });
</script>

</body>
</html>