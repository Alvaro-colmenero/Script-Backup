<!DOCTYPE html>
<html>
<head>
    <title>Mail Backup</title>
    <link rel="stylesheet" href="./css/styles.css">
</head>
<body>
<div class="form-container">
    <h2>Backup de correo</h2>
    <form id="backupForm" method="POST" action="backup.php">
        <input type="text" name="email" placeholder="Correo" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        <input type="text" name="imap" placeholder="imap.servidor.com" required>
        <input type="number" name="port" value="993">
        <button type="submit">Crear Backup</button>
    </form>

    <!-- Barra de progreso -->
    <div class="progress-container" id="progressContainer" style="display: none;">
        <div class="progress-bar" id="progressBar"></div>
        <span id="progressText">0%</span>
    </div>
</div>

<script>
    const form = document.getElementById('backupForm');
    const progressContainer = document.getElementById('progressContainer');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');

    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevenir envío real por ahora

        // Mostrar barra de progreso
        progressContainer.style.display = 'block';
        progressBar.style.width = '0%';
        progressText.textContent = '0%';

        let progress = 0;

        // Simulación de carga
        const interval = setInterval(() => {
            progress += Math.floor(Math.random() * 10) + 5; // Incremento aleatorio
            if(progress >= 100) progress = 100;

            progressBar.style.width = progress + '%';
            progressText.textContent = progress + '%';

            if(progress >= 100) {
                clearInterval(interval);
                progressText.textContent = 'Backup completado!';

                // Enviar el formulario después de la simulación
                // form.submit(); // Descomentar si quieres enviar a backup.php
            }
        }, 500);
    });
</script>
</body>
</html>