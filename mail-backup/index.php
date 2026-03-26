<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mail Backup Pro</title>
    <link rel="stylesheet" href="./css/styles.css">
    <style>
        .hidden { display: none; }
        select, input { width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px; border: 1px solid #ccc; box-sizing: border-box; }
    </style>
</head>
<body>
<div class="form-container">
    <h2>Backup de correo</h2>
    <form id="backupForm" method="POST" action="backup.php">
        <label for="auth_type">Método de conexión:</label>
        <select name="auth_type" id="auth_type" onchange="toggleCpanelField()">
            <option value="direct">Correo + Contraseña Individual</option>
            <option value="cpanel">Acceso Maestro (cPanel)</option>
        </select>

        <input type="text" name="email" placeholder="Correo a respaldar (ej: info@dominio.com)" required>

        <div id="cpanel_user_div" class="hidden">
            <input type="text" name="cpanel_user" id="cpanel_user" placeholder="Usuario de tu cPanel">
        </div>

        <input type="password" name="password" placeholder="Contraseña (del correo o del cPanel)" required>
        <input type="text" name="imap" placeholder="imap.tuservidor.com" required>
        <input type="number" name="port" value="993">

        <button type="submit">Crear Backup</button>
    </form>

</div>

</body>
</html>