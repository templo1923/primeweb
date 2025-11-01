<?php

include "session.php";
include "header.php"; // Incluir el menú
include "includes/functions.php";
include "config.php";

$id = $_GET["id"];
$tmdb_api_key = "54a47dfd36406757c9a6b7a0e3fd9cdc"; // Tu clave de API de TMDb

// Definir el User-Agent
$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36';

// Función para realizar una solicitud API usando CURL
function getApiData($url) {
    global $userAgent;
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

// Obtener la información desde Xtream Codes
$xtream_url = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_vod_info&vod_id=" . $id;
$channel_api = getApiData($xtream_url);
$channel_api2 = $channel_api["info"];
$channel_api3 = $channel_api["movie_data"];

$title = $channel_api2["name"];
$description = $channel_api2["plot"];
$youtube_trailer = $channel_api2["youtube_trailer"];
$movie_genre = $channel_api2["genre"];
$releasedate = $channel_api2["releasedate"];
$movie_rating = $channel_api2["rating"];
$container_extension = $channel_api3["container_extension"];
$backdrop = $channel_api2["backdrop_path"][0] ?? '';

// Verificar si el poster es válido, de lo contrario usar uno por defecto
$img = isset($channel_api2["movie_image"]) && !empty($channel_api2["movie_image"]) 
    ? $channel_api2["movie_image"] 
    : "https://i.imgur.com/Mn7aXQD.jpg"; // Poster por defecto si no hay imagen disponible

// Obtener el ID de la película en TMDb
$tmdb_url = "https://api.themoviedb.org/3/search/movie?api_key={$tmdb_api_key}&query=" . urlencode($title);
$tmdb_movie_data = getApiData($tmdb_url);
$tmdb_movie_id = $tmdb_movie_data['results'][0]['id'] ?? null;

// Obtener el reparto desde TMDb
$cast_list = '';
if ($tmdb_movie_id) {
    $tmdb_cast_url = "https://api.themoviedb.org/3/movie/{$tmdb_movie_id}/credits?api_key={$tmdb_api_key}";
    $cast_data = getApiData($tmdb_cast_url);
    $cast = $cast_data['cast'] ?? [];
    $max_cast = 5; // Limitar a los 5 actores principales

    foreach (array_slice($cast, 0, $max_cast) as $actor) {
        $actor_img = "https://image.tmdb.org/t/p/w200" . ($actor["profile_path"] ?? '');
        $actor_name = $actor["name"];
        $character = $actor["character"];
        
        // Si no hay foto del actor, usamos una imagen por defecto
        if (empty($actor["profile_path"])) {
            $actor_img = "https://i.imgur.com/placeholder.png"; // Reemplaza con tu propia imagen de placeholder
        }
        
        $cast_list .= "
            <div class='actor'>
                <img src='{$actor_img}' alt='{$actor_name}'>
                <div class='actor-name'>{$actor_name}</div>
                <div class='actor-character'>as {$character}</div>
            </div>
        ";
    }
}

echo "
<html lang=\"en\">
<head>
    <meta charset=\"utf-8\">
    <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
    <link rel=\"icon\" href=\"assets/img/favicon.ico\" type=\"image/ico\">
    <title>{$sitename} - Movies On Demand</title>
    <link href=\"assets/css/bootstrap.min.css\" rel=\"stylesheet\">
    <link href=\"assets/css/font-awesome.min.css\" rel=\"stylesheet\">
    <link href=\"assets/css/style.css\" rel=\"stylesheet\">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: Arial, sans-serif;
            color: #fff;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('{$backdrop}') no-repeat center center fixed;
            background-size: cover;
            z-index: -1;
        }

        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6); /* Overlay negro con 50% de transparencia */
            z-index: -1;
        }

        .content-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            padding: 20px;
            box-sizing: border-box;
        }

        .movie-container {
            display: flex;
            align-items: flex-start;
            max-width: 1000px;
            width: 100%;
        }

        .poster {
            flex: 1;
            max-width: 300px;
            margin-right: 50px;
        }

        .poster img {
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.7);
        }

        .movie-details {
            flex: 2;
        }

        .movie-details h1 {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .movie-details p {
            font-size: 18px;
            line-height: 1.6;
            color: #ccc;
        }

        .movie-details .genre-release {
            margin: 10px 0;
            font-size: 20px;
            color: #bbb;
        }

        .rating {
            margin-top: 10px;
            font-size: 14px;
            color: #FFD700;
        }

        .buttons {
            display: flex;
            margin-top: 20px;
        }

        .button-primary {
            background-color: #e50914;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
            display: inline-block;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .button-primary:hover {
            background-color: #b20710;
        }

        .trailer-button {
            background-color: #333;
            margin-left: 10px;
        }

        .trailer-button:hover {
            background-color: #555;
        }

        .cast {
            margin-top: 20px;
            text-align: center;
        }

        .cast .actor {
            display: inline-block;
            text-align: center;
            margin-right: 15px;
        }

        .cast .actor img {
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

        /* Estilo del modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #1c1c1c;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 800px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: white;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class=\"content-wrapper\">
    <div class=\"movie-container\">
        <div class=\"poster\">
            <img src=\"{$img}\" alt=\"{$title}\">
        </div>
        <div class=\"movie-details\">
            <h1>{$title}</h1>
            <div class=\"genre-release\">
                {$movie_genre} | {$releasedate}
            </div>
            <div class=\"rating\">Rating: {$movie_rating}/10</div>
            <p>{$description}</p>
            <div class=\"buttons\">
                <a href=\"video_player?id={$id}&slug=movie&ext={$container_extension}\" class=\"button-primary\">Watch Now</a>
                <button class=\"button-primary trailer-button\" id=\"openModal\">Watch Trailer</button>
            </div>
            <div class=\"cast\">
                {$cast_list}
            </div>
        </div>
    </div>
</div>

<!-- Modal para el trailer -->
<div id=\"trailerModal\" class=\"modal\">
    <div class=\"modal-content\">
        <span class=\"close\" id=\"closeModal\">&times;</span>
        <iframe width='100%' height='400px' src='https://www.youtube.com/embed/{$youtube_trailer}' frameborder='0' allow='autoplay; encrypted-media' allowfullscreen></iframe>
    </div>
</div>

<script>
    // Obtener el modal
    var modal = document.getElementById('trailerModal');

    // Obtener el botón que abre el modal
    var btn = document.getElementById('openModal');

    // Obtener el elemento <span> que cierra el modal
    var span = document.getElementById('closeModal');

    // Cuando el usuario haga clic en el botón, abrir el modal
    btn.onclick = function() {
        modal.style.display = 'flex';
    }

    // Cuando el usuario haga clic en <span> (x), cerrar el modal
    span.onclick = function() {
        modal.style.display = 'none';
    }

    // Cuando el usuario haga clic fuera del modal, cerrarlo
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
</script>

</body>
</html>
";
?>
