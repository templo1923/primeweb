<?php
// Activar el reporte de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Conexión a SQLite utilizando el archivo .dns.db
    $db = new PDO('sqlite:dns.dns.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verificar si la tabla `servers` existe y crearla si no
    $db->exec("CREATE TABLE IF NOT EXISTS servers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        address TEXT NOT NULL
    );");

    // Verifica si el usuario es administrador
    if ($_COOKIE["admin"] != "true") {
        header("location: ../../index.php");
        exit();
    }

    // Procesa el formulario de agregar DNS
    if (isset($_POST["add_dns"])) {
        if (!empty($_POST['dns_address'])) {
            $stmt = $db->prepare("INSERT INTO servers (address) VALUES (:address)");
            $stmt->bindParam(':address', $_POST['dns_address']);
            $stmt->execute();
        }
        header("Location: edit_dns.php");
        exit();
    }

    // Procesa el formulario de eliminar DNS
    if (isset($_POST["delete_dns"])) {
        $stmt = $db->prepare("DELETE FROM servers WHERE id = :id");
        $stmt->bindParam(':id', $_POST['dns_id'], PDO::PARAM_INT);
        $stmt->execute();
        header("Location: edit_dns.php");
        exit();
    }

    // Procesa el formulario de editar DNS
    if (isset($_POST["edit_dns"])) {
        if (!empty($_POST['dns_address'])) {
            $stmt = $db->prepare("UPDATE servers SET address = :address WHERE id = :id");
            $stmt->bindParam(':address', $_POST['dns_address']);
            $stmt->bindParam(':id', $_POST['dns_id'], PDO::PARAM_INT);
            $stmt->execute();
        }
        header("Location: edit_dns.php");
        exit();
    }

    // Obtiene los DNS existentes
    $servers = $db->query("SELECT * FROM servers")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Muestra el error
    echo "Error: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Admin Page</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.5.2/animate.min.css">
</head>
<style>
/* Estilos CSS */
body { background-color: #111; color: white; display: flex; }
.sidebar { width: 200px; background-color: #333; padding-top: 20px; position: fixed; height: 100%; }
.sidebar a { color: white; padding: 15px; text-decoration: none; display: block; text-align: left; }
.sidebar a:hover { background-color: #ddd; color: black; }
.container { margin-left: 220px; padding: 20px; width: calc(100% - 220px); }
.card { margin-bottom: 20px; border: 1px solid #444; border-radius: 8px; padding: 20px; background-color: #222; box-shadow: 0 0 15px rgba(0, 0, 0, 0.3); }
.center-div { text-align: center; margin-top: 20px; }
.input-group { display: flex; align-items: center; }
input[type="text"] { background-color: #333; color: white; border: 1px solid #555; border-radius: 5px; padding: 10px; flex: 1; }
input[type="text"]::placeholder { color: #bbb; }
.form-group { margin-bottom: 20px; }
</style>
<body>

<!-- Menú Vertical -->
<div class="sidebar">
    <a href="edit_dns.php">DNS Settings</a>
    <a href="../../includes/admin/edit_admin.php">Change Admin User/Pass</a>
    <a href="../../login.php">Login Page</a>
</div>

<div class="container">
  <h2>Admin Portal - DNS Management</h2><br>

  <!-- Formulario para agregar nuevo DNS -->
  <div class="card">
    <form method="POST">
        <div class="form-group">
            <label for="dns_address">New DNS Address:</label>
            <input type="text" class="form-control" name="dns_address" placeholder="Enter DNS:PORT">
        </div>
        <button type="submit" name="add_dns" class="btn btn-primary">Add DNS</button>
    </form>
  </div>

  <!-- Listado de DNS existentes -->
  <div class="card">
    <h3>Current DNS Addresses</h3>
    <?php foreach ($servers as $server): ?>
    <div class="form-group">
        <div class="input-group">
            <form method="POST" style="display: flex; flex: 1;">
                <input type="hidden" name="dns_id" value="<?= $server['id'] ?>">
                <input type="text" class="form-control" name="dns_address" value="<?= htmlspecialchars($server['address']) ?>" placeholder="Enter DNS:PORT">
                <button type="submit" name="edit_dns" class="btn btn-success btn-sm ml-2">Edit</button>
            </form>
            <form method="POST" style="margin-left: 5px;">
                <input type="hidden" name="dns_id" value="<?= $server['id'] ?>">
                <button type="submit" name="delete_dns" class="btn btn-danger btn-sm">Delete</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>

<script>
$(document).ready(function () {
    $("#flash-msg").delay(3000).fadeOut("slow");
});
</script>

</body>
</html>
