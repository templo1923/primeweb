<?php
include "session.php";
include "header.php";
include "includes/functions.php";
include "config.php";

$id = $_GET["id"];
$slug = $_GET["slug"];
$ext = isset($_GET["ext"]) ? $_GET["ext"] : "mp4";

$video_url = ($slug == "movie")
    ? $get_dns . "/movie/" . $username . "/" . $password . "/" . $id . "." . $ext
    : $get_dns . "/series/" . $username . "/" . $password . "/" . $id . "." . $ext;

$content_name = "";
$release_year = "";
$rating = "";

if ($slug == "movie") {
    $movie_info = getXtreamData('get_vod_info', ['vod_id' => $id]);
    $content_name = $movie_info['info']['name'];
    $release_year = isset($movie_info['info']['releasedate']) ? substr($movie_info['info']['releasedate'], 0, 4) : 'N/A';
    $rating = isset($movie_info['info']['rating']) ? $movie_info['info']['rating'] : 'N/A';
} elseif ($slug == "series") {
    $series_info = getXtreamData('get_series_info', ['series_id' => $_GET['series_id']]);
    $content_name = $series_info['info']['name'];
    $release_year = isset($series_info['info']['releaseDate']) ? substr($series_info['info']['releaseDate'], 0, 4) : 'N/A';
    $rating = isset($series_info['info']['rating']) ? $series_info['info']['rating'] : 'N/A';
}

function getXtreamData($endpoint, $params = []) {
    global $get_dns, $username, $password;
    $url = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=" . $endpoint;
    if (!empty($params)) {
        $url .= '&' . http_build_query($params);
    }
    $response = file_get_contents($url);
    return json_decode($response, true);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reproductor de Video</title>
    <link href="https://vjs.zencdn.net/7.11.4/video-js.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.plyr.io/3.6.8/plyr.css" />
    <script src="https://vjs.zencdn.net/7.11.4/video.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/clappr@latest/dist/clappr.min.js"></script>
    <script src="https://cdn.plyr.io/3.6.8/plyr.js"></script>

    <style>
        body {
            background-color: #141b29;
            color: white;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            flex-direction: column;
        }
        .banner {
            background: linear-gradient(135deg, #2c3e50, #4ca1af);
            padding: 15px;
            border-radius: 10px;
            overflow: hidden;
            font-size: 1.5em;
            width: 90%;
            max-width: 1200px;
            text-align: center;
            margin-top: 60px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.7);
            position: relative;
        }
        .banner-content {
            display: inline-block;
            animation: marquee 10s linear infinite;
            white-space: nowrap;
        }
        @keyframes marquee {
            0% { transform: translateX(130%); }
            100% { transform: translateX(-150%); }
        }
        .container {
            width: 90%;
            max-width: 1200px;
            background: rgba(0, 0, 0, 0.85);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.8);
            position: relative;
            margin-top: 20px;
        }
        .video-container {
            position: relative;
            width: 100%;
            height: 700px;
            margin-top: 20px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.7);
        }
        .back-button, .change-player-button {
            position: absolute;
            top: 80px;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            color: #fff;
            background-color: #1e90ff;
            cursor: pointer;
            transition: background-color 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .back-button {
            left: 200px;
        }
        .change-player-button {
            right: 100px;
        }
        .back-button:hover, .change-player-button:hover {
            background-color: #3498db;
        }
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 10;
        }
        .modal-content {
            background: #1c1c1c;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            text-align: center;
            color: #fff;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.8);
        }
        .modal-content button {
            background-color: #3498db;
            padding: 10px 20px;
            border: none;
            color: #fff;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .modal-content button:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <!-- Botón de Regreso -->
    <a class="back-button" href="<?php echo ($slug == 'movie') ? 'Movie_description.php?id=' . $id : 'seriesvideo.php?id=' . $_GET['series_id']; ?>">
        ⬅ Regresar
    </a>

    <!-- Botón para cambiar de reproductor -->
    <button class="change-player-button" onclick="openModal()">Reproductores</button>

  <!-- Banner de Información en Movimiento -->
<div class="banner">
    <div class="banner-content">
        <span>🎬 <?php echo htmlspecialchars($content_name); ?></span> |
        <span>📅 Año: <?php echo $release_year; ?></span> |
        <span>⭐ Calificación: <?php echo $rating; ?></span>
    </div>
</div>

    <div class="container">
        <!-- Selector de Reproductor (Modal) -->
        <div class="modal-overlay" id="modal">
            <div class="modal-content">
                <h2>Reproductores</h2>
                <div class="player-selector">
                    <button onclick="loadPlayer('videojs')">Video.js</button>
                    <button onclick="loadPlayer('clappr')">Clappr</button>
                    <button onclick="loadPlayer('plyr')">Plyr</button>
                </div>
                <button onclick="closeModal()">Cerrar</button>
            </div>
        </div>

        <!-- Contenedor del Video -->
        <div id="video-container" class="video-container">
            <!-- Reproductor de Video.js -->
            <video id="videojs-player" class="video-js vjs-default-skin" controls preload="auto" style="display: none; width: 100%; height: 100%;">
                <source src="<?php echo $video_url; ?>" type="video/mp4">
            </video>

            <!-- Contenedor de Clappr -->
            <div id="clappr-player" style="display: none; width: 100%; height: 100%;"></div>

            <!-- Contenedor de Plyr -->
            <video id="plyr-player" class="plyr" controls style="display: none; width: 100%; height: 100%;">
                <source src="<?php echo $video_url; ?>" type="video/mp4">
            </video>
        </div>
    </div>

    <script>
        let videojsPlayer, plyrPlayer, clapprPlayer;

        // Abrir Modal
        function openModal() {
            document.getElementById("modal").style.display = "flex";
        }

        // Cerrar Modal
        function closeModal() {
            document.getElementById("modal").style.display = "none";
        }

        // Mostrar modal al cargar la página
        window.onload = function() {
            openModal();
        };

        function loadPlayer(player) {
            closeModal();

            // Detener reproducción de cualquier reproductor activo
            if (videojsPlayer) videojsPlayer.dispose();
            if (plyrPlayer) plyrPlayer.destroy();
            if (clapprPlayer) {
                clapprPlayer.destroy();
                clapprPlayer = null;
            }

            // Ocultar todos los reproductores primero
            document.getElementById('videojs-player').style.display = 'none';
            document.getElementById('clappr-player').style.display = 'none';
            document.getElementById('plyr-player').style.display = 'none';

            // Configurar el reproductor seleccionado
            if (player === 'videojs') {
                document.getElementById('videojs-player').style.display = 'block';
                videojsPlayer = videojs('videojs-player', { autoplay: true });
            } else if (player === 'clappr') {
                document.getElementById('clappr-player').style.display = 'block';
                clapprPlayer = new Clappr.Player({
                    source: "<?php echo $video_url; ?>",
                    mimeType: "video/mp4",
                    parentId: "#clappr-player",
                    width: '100%',
                    height: '100%',
                    autoPlay: true,
                });
            } else if (player === 'plyr') {
                document.getElementById('plyr-player').style.display = 'block';
                plyrPlayer = new Plyr('#plyr-player', { autoplay: true });
            }
        }
    </script>
</body>
</html>
