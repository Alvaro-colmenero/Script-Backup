<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mail Backup Pro - Control de Progreso</title>
    <style>
        .progress-container {
            width: 100%;
            background: #eee;
            border-radius: 8px;
            margin-top: 20px;
            height: 30px;
            position: relative;
            overflow: hidden;
            display: none;
            border: 1px solid #ddd;
        }
        .progress-bar {
            width: 0%;
            height: 100%;
            background: #28a745;
            transition: width 0.4s ease; /* Transición suave */
        }
        #progressText {
            position: absolute;
            width: 100%;
            text-align: center;
            top: 5px;
            font-weight: bold;
            color: #333;
            z-index: 1;
        }
        .form-container { max-width: 500px; margin: 50px auto; font-family: sans-serif; }
        input, select, button { width: 100%; padding: 10px; margin: 5px 0; box-sizing: border-box; }
        button { background: #007bff; color: white; border: none; cursor: pointer; }
        button:disabled { background: #ccc; }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Respaldo de Emails</h2>
    <form id="backupForm" method="POST" action="backup.php" target="worker">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        <input type="text" name="imap" placeholder="imap.servidor.com" required>
        <input type="number" name="port" value="993">
        <button type="submit" id="btnSubmit">Empezar Backup</button>
    </form>

    <div class="progress-container" id="progressContainer">
        <div id="progressText">Esperando respuesta...</div>
        <div class="progress-bar" id="progressBar"></div>
    </div>

    <div id="resultArea" style="margin-top:20px; text-align: center;"></div>
</div>

<iframe name="worker" id="workerFrame" style="display:none;"></iframe>

<script>
    const form = document.getElementById('backupForm');
    let progressInterval;

    form.addEventListener('submit', function() {
        // 1. Preparamos la interfaz
        document.getElementById('progressContainer').style.display = 'block';
        document.getElementById('progressBar').style.width = '0%';
        document.getElementById('btnSubmit').disabled = true;
        document.getElementById('resultArea').innerHTML = "Iniciando proceso en el servidor...";

        // 2. Limpiamos cualquier intervalo previo
        if(progressInterval) clearInterval(progressInterval);

        // 3. Pequeño retraso de 800ms para dar tiempo al servidor a crear el archivo .txt
        setTimeout(() => {
            progressInterval = setInterval(() => {
                // El parámetro ?t= asegura que el navegador NO use una respuesta vieja (Cache Busting)
                fetch('progress.php?t=' + Date.now())
                    .then(response => {
                        if (!response.ok) throw new Error("Error de red");
                        return response.json();
                    })
                    .then(data => {
                        console.log("Progreso recibido:", data); // Para que lo veas en F12

                        if (data && typeof data.percent !== 'undefined') {
                            const porcentaje = parseInt(data.percent);

                            // Actualizamos la barra y el texto
                            document.getElementById('progressBar').style.width = porcentaje + '%';
                            document.getElementById('progressText').textContent = data.status + ' (' + porcentaje + '%)';

                            // Si llega al 100%, dejamos de preguntar
                            if (porcentaje >= 100) {
                                clearInterval(progressInterval);
                                document.getElementById('btnSubmit').disabled = false;
                            }
                        }
                    })
                    .catch(error => {
                        console.error("Error en la petición de progreso:", error);
                    });
            }, 1000); // Consultamos cada 1 segundo
        }, 800);
    });

    // Esta función la puede llamar el iframe al terminar si quieres
    function finishBackup() {
        if(progressInterval) clearInterval(progressInterval);
        document.getElementById('btnSubmit').disabled = false;
    }
</script>

</body>
</html>