<?php
// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "header.php";
include "includes/functions.php";
include "config.php";

// Definir el User-Agent
$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36';

// Función para realizar una solicitud API usando CURL
function getApiData($url, $userAgent) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        echo 'Error de CURL: ' . curl_error($ch);
        curl_close($ch);
        return [];
    }

    curl_close($ch);

    if ($httpCode !== 200) {
        echo "Error al conectarse a la API. Código HTTP: " . $httpCode;
        return [];
    }

    return json_decode($response, true);
}

// Obtener el ID de la serie y la temporada seleccionada (si existe)
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$selected_season = isset($_GET['season']) ? (int)$_GET['season'] : null;

$tmdb_api_key = "54a47dfd36406757c9a6b7a0e3fd9cdc"; // Tu clave de API de TMDb

// Validar que el ID de la serie esté disponible
if (!$id) {
    die("No se proporcionó ID de la serie.");
}

// URL de la API para obtener información de la serie
$api_url = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_series_info&series_id=" . $id;
$series_data = getApiData($api_url, $userAgent);

// Verificar si la API devolvió datos válidos
if (!isset($series_data['info'])) {
    die("No se pudo obtener información de la serie.");
}

// Obtener la información de la serie
$series_info = $series_data['info'];
$title = $series_info['name'];
$description = $series_info['plot'];
$backdrop = isset($series_info['backdrop_path'][0]) ? $series_info['backdrop_path'][0] : "https://i.imgur.com/5rF4SnC.jpg"; // Imagen por defecto
$poster = isset($series_info['cover']) ? $series_info['cover'] : "https://i.imgur.com/Mn7aXQD.jpg";

// Buscar la serie en TMDb por título para obtener el ID de TMDb
$tmdb_id = null; // Inicializar la variable
$tmdb_url = "https://api.themoviedb.org/3/search/tv?api_key={$tmdb_api_key}&query=" . urlencode($title);
$tmdb_data = getApiData($tmdb_url, $userAgent);
if (isset($tmdb_data['results']) && count($tmdb_data['results']) > 0) {
    $tmdb_id = $tmdb_data['results'][0]['id']; // Asignar el ID si hay resultados
}

// Obtener el cast de la serie desde TMDb
$cast_list = '';
if ($tmdb_id) {
    $tmdb_cast_url = "https://api.themoviedb.org/3/tv/{$tmdb_id}/credits?api_key={$tmdb_api_key}";
    $tmdb_cast_data = getApiData($tmdb_cast_url, $userAgent);
    $cast = $tmdb_cast_data['cast'] ?? [];
    $max_cast = 5; // Limitar a los 5 actores principales

    foreach (array_slice($cast, 0, $max_cast) as $actor) {
        $actor_img = "https://image.tmdb.org/t/p/w200" . ($actor['profile_path'] ?? '');
        $actor_name = $actor['name'];
        $character = $actor['character'];
        
        if (empty($actor['profile_path'])) {
            $actor_img = "https://i.imgur.com/placeholder.png"; // Imagen por defecto si no hay perfil
        }
        
        $cast_list .= "
            <div class='actor'>
                <img src='{$actor_img}' alt='{$actor_name}' class='actor-img'>
                <div class='actor-name'>{$actor_name}</div>
                <div class='actor-character'>as {$character}</div>
            </div>
        ";
    }
}

// Obtener las temporadas y episodios
$seasons = isset($series_data['episodes']) ? $series_data['episodes'] : [];

// Si no se ha seleccionado temporada, seleccionar la primera
if ($selected_season === null && !empty($seasons)) {
    reset($seasons); // Mover el puntero al primer elemento
    $selected_season = key($seasons); // Obtener la clave del primer elemento
}

// Obtener episodios de la temporada seleccionada
$episodes = isset($seasons[$selected_season]) ? $seasons[$selected_season] : [];

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $sitename . ' - ' . $title; ?></title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/font-awesome.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #141414;
            color: white;
            font-family: Arial, sans-serif;
            overflow-y: scroll; /* Permitir scroll vertical */
            scrollbar-width: thin; /* Scrollbar delgada en Firefox */
            scrollbar-color: #888 #1c1c1c; /* Color del scrollbar */
        }

        /* Para navegadores basados en Webkit (Chrome, Safari) */
        body::-webkit-scrollbar {
            width: 8px; /* Ancho del scrollbar */
        }

        body::-webkit-scrollbar-track {
            background: #1c1c1c; /* Color de fondo del scrollbar */
            border-radius: 8px; /* Bordes redondeados */
        }

        body::-webkit-scrollbar-thumb {
            background-color: #888; /* Color del scrollbar */
            border-radius: 8px; /* Bordes redondeados */
        }

        body::-webkit-scrollbar-thumb:hover {
            background-color: #555; /* Color al pasar el mouse */
        }

        .hero {
            background-image: url('<?php echo $backdrop; ?>');
            background-size: cover;
            background-position: center;
            position: relative;
            color: white;
            padding: 50px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.6); /* Overlay oscuro */
        }
        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }
        .hero img {
            max-width: 300px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.7);
        }
        .hero h1 {
            font-size: 3em;
            margin-bottom: 10px;
        }
        .hero p {
            font-size: 1.2em;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Botones centrados */
        .buttons {
            margin-top: 20px;
            text-align: center;
        }

        .button {
            background-color: #e50914;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
            font-size: 16px;
            text-decoration: none;
        }

        .button:hover {
            background-color: #b20710;
        }

        /* Estilo para las temporadas */
        .season-buttons {
            margin-top: 20px;
            text-align: center;
        }
        .season-button {
            background-color: #333;
            color: white;
            padding: 10px;
            margin-right: 10px;
            border: none;
            cursor: pointer;
            display: inline-block;
            border-radius: 5px;
            font-size: 1.1em;
        }
        .season-button.active {
            background-color: #e50914;
        }

        /* Modal para episodios */
        .modal {
            display: none; /* Oculto por defecto */
            position: fixed; /* Fijo en pantalla */
            z-index: 1000; /* Encima de otros elementos */
            left: 0;
            top: 0;
            width: 100%; /* Ancho completo */
            height: 100%; /* Alto completo */
            overflow: auto; /* Permitir scroll si es necesario */
            background-color: rgba(0, 0, 0, 0.8); /* Fondo oscuro */
            justify-content: center; /* Centrar contenido en el modal */
            align-items: center; /* Centrar contenido en el modal */
        }

        .modal-content {
            background-color: #1c1c1c;
            margin: 15% auto; /* Margen superior y centrado */
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Ancho del modal */
            border-radius: 8px; /* Bordes redondeados */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Sombra */
            max-height: 80%; /* Limitar la altura del modal */
            overflow-y: auto; /* Habilitar scroll vertical */
        }

        .close {
            color: #aaa;
            float: right; /* Alinear a la derecha */
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black; /* Color al pasar el mouse */
        }

        /* Estilo para los episodios en el modal */
        .episodes-carousel {
            display: flex;
            overflow-x: auto;
            padding: 20px 0;
            gap: 15px;
        }

        .episode {
            flex: 0 0 200px;
            background-color: #222;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.3s ease;
            text-align: center;
        }

        .episode img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .episode-title {
            padding: 10px;
            text-align: center;
            color: white;
        }

        .episode:hover {
            transform: scale(1.1);
        }

        /* Cast estilo horizontal */
        .cast {
            margin-top: 20px;
            text-align: center;
        }

        .cast .actor {
            display: inline-block;
            text-align: center;
            margin-right: 15px;
        }

        .cast .actor-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.7);
            margin-bottom: 10px;
        }

        .cast .actor-name {
            font-size: 16px;
            font-weight: bold;
        }

        .cast .actor-character {
            font-size: 14px;
            color: #aaa;
        }

    </style>
</head>
<body>
<div class="hero">
    <div class="hero-content">
        <img src="<?php echo $poster; ?>" alt="<?php echo $title; ?>">
        <h1><?php echo $title; ?></h1>
        <p><?php echo $description; ?></p>
    </div>
</div>

<div class="container">
    <div class="season-buttons">
        <!-- Botones de temporada -->
        <?php foreach ($seasons as $season_number => $season_episodes): ?>
            <a href="?id=<?php echo $id; ?>&season=<?php echo $season_number; ?>" class="season-button <?php echo ($season_number == $selected_season) ? 'active' : ''; ?>">
                Temporada <?php echo $season_number; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="buttons">
        <button class="button" id="openModal">Ver episodios</button>
    </div>

    <div class="cast">
        <?php echo $cast_list; ?>
    </div>
</div>

<!-- Modal para episodios -->
<div id="episodesModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeModal">&times;</span>
        <div class="episodes-carousel" id="modal-episodes-carousel">
            <!-- Los episodios del modal se mostrarán aquí -->
        </div>
    </div>
</div>

<script>
    // Función para llenar episodios en el modal
    function loadSeason(seasonNumber) {
        const episodes = <?php echo json_encode($seasons); ?>[seasonNumber];
        const carousel = document.getElementById('modal-episodes-carousel');
        carousel.innerHTML = ''; // Limpiar el contenido del carrusel

        episodes.forEach(function(episode) {
            const playUrl = `video_player?id=${episode.id}&slug=series&ext=${episode.container_extension}&series_id=<?php echo $id; ?>`;
            const episodeHtml = `
                <div class="episode">
                    <a href="${playUrl}">
                        <img src="${episode.info.movie_image}" alt="${episode.title}">
                        <div class="episode-title">${episode.title || 'Sin título'}</div>
                    </a>
                </div>
            `;
            carousel.insertAdjacentHTML('beforeend', episodeHtml);
        });
    }

    // Obtener el modal
    var episodesModal = document.getElementById('episodesModal');
    var btn = document.getElementById('openModal');
    var closeBtn = document.getElementById('closeModal');

    // Abrir el modal al hacer clic en el botón
    btn.onclick = function() {
        episodesModal.style.display = 'flex';
        loadSeason(<?php echo $selected_season; ?>);
    }

    // Cerrar el modal al hacer clic en la 'X'
    closeBtn.onclick = function() {
        episodesModal.style.display = 'none';
    }

    // Cerrar el modal al hacer clic fuera del modal
    window.onclick = function(event) {
        if (event.target == episodesModal) {
            episodesModal.style.display = 'none';
        }
    }

    // Cerrar el modal con la tecla Esc
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            if (episodesModal.style.display === 'flex') {
                episodesModal.style.display = 'none';
            }
        }
    });
</script>

</body>
</html>
