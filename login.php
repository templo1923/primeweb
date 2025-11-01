<?php
session_start();
include "includes/functions.php";
include "config.php"; // Configuración que carga las credenciales de administrador

// Verificar si ya hay una sesión activa para redirigir a `homex.php`
if (isset($_SESSION["username"])) {
    header("Location: homex.php");
    exit();
}

// Función para obtener las películas desde la API con user agent
function getMoviesFromEndpoint($api_key, $pages = 10) {
    $cache_file = 'movies_cache.json';
    $cache_time = 3600; // 1 hora
    $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36';

    // Verificar si existe el archivo de caché y si está dentro del tiempo válido
    if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
        return json_decode(file_get_contents($cache_file), true);
    }

    $movies = [];
    for ($page = 1; $page <= $pages; $page++) {
        $url = "https://api.themoviedb.org/3/movie/now_playing?api_key={$api_key}&language=es-ES&page={$page}";
        
        $context = stream_context_create([
            'http' => [
                'header' => "User-Agent: $userAgent\r\n"
            ]
        ]);

        $response = file_get_contents($url, false, $context);
        $data = json_decode($response, true);
        if (isset($data['results']) && is_array($data['results'])) {
            $movies = array_merge($movies, $data['results']);
        }
    }

    // Filtrar películas que tengan un poster_path válido
    $movies = array_filter($movies, function($movie) {
        return isset($movie['poster_path']) && $movie['poster_path'] !== null;
    });

    // Guardar en el archivo de caché
    file_put_contents($cache_file, json_encode($movies));
    return $movies;
}

// Obtener películas desde la API de TMDb usando la función `getMoviesFromEndpoint`
$api_key = '54a47dfd36406757c9a6b7a0e3fd9cdc';
$movies = getMoviesFromEndpoint($api_key);
$total_movies = count($movies);

try {
    // Conexión a la base de datos SQLite en la ruta correcta
    $db = new PDO('sqlite:./includes/dns/dns.dns.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Crear la tabla `servers` si no existe
    $db->exec("CREATE TABLE IF NOT EXISTS servers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        address TEXT NOT NULL
    );");

    // Obtener los DNS disponibles
    $dns_servers = $db->query("SELECT * FROM servers")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

// Procesamiento del formulario de inicio de sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["username"]) && isset($_POST["password"])) {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36';

    // Verificación de credenciales de administrador desde `config.php`
    if ($username === $adminU && $password === $adminP) {
        setcookie("admin", "true", time() + (86400 * 30), "/");  // Cookie de administrador por 30 días
        $_SESSION["username"] = $adminU;
        $_SESSION["admin"] = true;
        echo json_encode(['success' => true, 'redirect' => 'includes/dns/edit_dns.php']);
        exit();
    }

    // Probar autenticación en cada servidor DNS
    foreach ($dns_servers as $server) {
        $XC_PORTAL = $server['address'];
        $opts = [
            'http' => [
                'header' => "User-Agent: $userAgent\r\n"
            ]
        ];
        $context = stream_context_create($opts);

        $api = json_decode(file_get_contents($XC_PORTAL . "/player_api.php?username=" . $username . "&password=" . $password, false, $context), true);

        if ($api && isset($api["user_info"]["auth"]) && $api["user_info"]["auth"] == 1) {
            $_SESSION["username"] = $username;
            $_SESSION["password"] = $password;
            $_SESSION["server"] = $XC_PORTAL;
            echo json_encode(['success' => true, 'redirect' => 'homex']);
            exit();
        }
    }

    echo json_encode(['success' => false, 'message' => 'No se pudo autenticar con ninguno de los servidores DNS disponibles']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Login - Carrusel de Películas</title>
    <style>
        body, html { margin: 0; padding: 0; height: 100%; background-color: #181828; color: #fff; font-family: Arial, sans-serif; overflow: hidden; }
        .carousel-container { position: fixed; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden; z-index: 1; }
        .carousel-content { display: flex; flex-wrap: wrap; justify-content: space-around; padding: 20px 0; height: 100%; }
        .poster { width: calc(20% - 20px); margin: 10px; position: relative; overflow: hidden; aspect-ratio: 2/3; cursor: pointer; transition: transform 0.3s ease; }
        .poster:hover { transform: scale(1.1); z-index: 10; }
        .poster img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .poster-title { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.7); color: white; padding: 5px; text-align: center; opacity: 0; transition: opacity 0.3s ease; }
        .poster:hover .poster-title { opacity: 1; }
        .content { position: relative; z-index: 2; text-align: center; padding-top: 50px; height: 100vh; display: flex; justify-content: center; align-items: center; flex-direction: column; }
        .login-box { background-color: rgba(0, 0, 0, 0.7); padding: 20px; border-radius: 10px; display: inline-block; width: 300px; }
        .login-box h1 { font-size: 40px; margin-bottom: 5px; }
        .login-box input { width: 90%; padding: 10px; margin-bottom: 10px; border: none; border-radius: 5px; }
        .login-box button { width: 100%; padding: 10px; background-color: #00509b; color: #fff; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
        .footer { position: fixed; bottom: 0; left: 0; width: 100%; background-color: rgba(0, 0, 0, 0.7); color: #fff; text-align: center; padding: 10px; z-index: 2; }
        .footer a { color: #ffcc00; text-decoration: none; }
        .logo { width: 250px; margin-bottom: 5px; }
    </style>
</head>
<body>
    <!-- Carrusel de Posters (Fondo) -->
    <div class="carousel-container">
        <div class="carousel-content">
            <?php 
            foreach ($movies as $movie) {
                echo '<div class="poster">';
                echo '<a href="https://www.themoviedb.org/movie/' . $movie['id'] . '" target="_blank">';
                echo '<img src="https://image.tmdb.org/t/p/w500' . $movie['poster_path'] . '" alt="' . $movie['title'] . '" loading="lazy">';
                echo '<div class="poster-title">' . $movie['title'] . '</div>';
                echo '</a>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <!-- Contenido Principal (login y footer) -->
    <div class="content" id="content">
        <div class="login-box">
            <img src="assets/img/logo.png" alt="Logo" class="logo">
            <h1>WELCOME</h1>
            <form id="login-form" method="POST">
                <input type="text" placeholder="Enter Username" id="username" name="username" required>
                <input type="password" placeholder="Enter Password" id="password" name="password" required>
                <button class="proceedLogin" type="submit">Login</button>
            </form>
        </div>
    </div>

    <div class="footer" id="footer">
        <p>© 2024 <a href="https://t.me/MJKeenan" target="_blank">Latinostreamz Rebrands</a></p>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('login-form').addEventListener('submit', function(event) {
            event.preventDefault();

            const formData = new FormData(this);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        });

        const content = document.querySelector('.carousel-content');
        let scrollPosition = 0;
        const scrollSpeed = 0.5;

        function scrollPosters() {
            scrollPosition += scrollSpeed;
            if (scrollPosition >= content.scrollHeight / 2) {
                scrollPosition = 0;
            }
            content.style.transform = `translateY(-${scrollPosition}px)`;
            requestAnimationFrame(scrollPosters);
        }

        requestAnimationFrame(scrollPosters);
    });
    </script>
</body>
</html>
