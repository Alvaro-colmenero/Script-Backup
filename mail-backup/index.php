<!DOCTYPE html>
<html>
<head>
    <title>Mail Backup</title>
</head>
<body>

<h2>Backup de correo</h2>

<form method="POST" action="backup.php">
    <input type="text" name="email" placeholder="Correo" required><br><br>
    <input type="password" name="password" placeholder="Contraseña" required><br><br>
    <input type="text" name="imap" placeholder="imap.servidor.com" required><br><br>
    <input type="number" name="port" value="993"><br><br>

    <button type="submit">Crear Backup</button>
</form>

</body>
</html>