<?php require 'config.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mail Restore Pro | Restauración IMAP</title>
    <style>
        /* Reset y Base */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        /* Contenedor */
        .form-container {
            background: #ffffff;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 460px;
            text-align: center;
        }

        h2 {
            color: #1a73e8;
            margin-bottom: 10px;
            font-size: 1.5rem;
        }

        p.subtitle {
            color: #5f6368;
            font-size: 0.9rem;
            margin-bottom: 25px;
        }

        /* Formulario */
        .form-group {
            text-align: left;
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 0.85rem;
            color: #3c4043;
        }

        input[type="text"],
        input[type="password"],
        input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #dadce0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #1a73e8;
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.15);
        }

        /* Botón */
        #btnSubmit {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 15px;
            width: 100%;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
            transition: background 0.3s;
        }

        #btnSubmit:hover { background-color: #218838; }
        #btnSubmit:disabled { background-color: #bdc3c7; cursor: not-allowed; }

        /* Barra de progreso */
        .progress-container {
            width: 100%;
            background: #e9ecef;
            border-radius: 20px;
            margin-top: 25px;
            height: 25px;
            position: relative;
            overflow: hidden;
            display: none;
            border: 1px solid #dee2e6;
        }

        .progress-bar {
            width: 0%;
            height: 100%;
            background: linear-gradient(90deg, #28a745, #34ce57);
            transition: width 0.4s ease;
        }

        #progressText {
            position: absolute;
            width: 100%;
            text-align: center;
            top: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            color: #333;
            z-index: 2;
        }

        /* Alertas */
        #resultArea { margin-top: 20px; }
        .error-panel {
            background-color: #fde8e8;
            color: #c81e1e;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #f8b4b4;
            font-size: 0.9rem;
        }
        .success-msg {
            background-color: #def7ec;
            color: #0e6245;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #84e1bc;
            font-size: 0.9rem;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Mail Restore Pro</h2>
    <p class="subtitle">Restaura correos desde un archivo ZIP a tu cuenta</p>

    <form id="restoreForm" method="POST" action="procesar_restauracion.php" enctype="multipart/form-data" target="worker">

        <div class="form-group">
            <label for="zip_file">Archivo ZIP (.zip):</label>
            <input type="file" name="zip_file" id="zip_file" accept=".zip" required>
        </div>

        <div class="form-group">
            <label for="email">Correo electrónico destino:</label>
            <input type="text" name="email" id="email" placeholder="ej: info@tu-dominio.com" required>
        </div>

        <div class="form-group">
            <label for="password">Contraseña (App Password):</label>
            <input type="password" name="password" id="password" placeholder="••••••••" required>
        </div>

        <div class="form-group">
            <label for="imap">Servidor IMAP:</label>
            <input type="text" name="imap" id="imap" value="imap.gmail.com" required>
        </div>

        <button type="submit" id="btnSubmit">INICIAR RESTAURACIÓN</button>
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
        // Reiniciar UI
        progContainer.style.display = 'block';
        btn.disabled = true;
        resultArea.innerHTML = "";
        progBar.style.width = '0%';
        progText.textContent = "Iniciando subida...";

        if(interval) clearInterval(interval);

        // Consultar progreso cada segundo
        interval = setInterval(() => {
            fetch('progress.php?t=' + Date.now())
                .then(res => res.json())
                .then(data => {
                    progBar.style.width = data.percent + '%';
                    progText.textContent = data.status + ' (' + data.percent + '%)';

                    // Manejo de Errores
                    if (data.status.toLowerCase().includes('error')) {
                        resultArea.innerHTML = `<div class="error-panel">${data.status}</div>`;
                        clearInterval(interval);
                        btn.disabled = false;
                    }

                    // Finalización
                    if(data.percent >= 100) {
                        clearInterval(interval);
                        btn.disabled = false;
                        resultArea.innerHTML = "<div class='success-msg'>¡Proceso completado con éxito!</div>";
                    }
                })
                .catch(err => console.error("Error consultando progreso:", err));
        }, 1000);
    });
</script>

</body>
</html>