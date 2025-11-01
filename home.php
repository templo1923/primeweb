<?php
// Incluir archivos necesarios
include "session.php";
include "config.php";
include "header.php";

// Función para obtener datos desde la API de Xtream Codes con CURL
function getApiData($apiUrl) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Error de CURL: ' . curl_error($ch);
        curl_close($ch);
        return [];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo "Error al conectarse a la API. Código HTTP: " . $httpCode;
        return [];
    }

    return json_decode($response, true);
}

// Función para obtener un backdrop o poster alternativo y el logo desde TMDb
function getTMDbImages($title) {
    $tmdbApiKey = "54a47dfd36406757c9a6b7a0e3fd9cdc";
    $tmdbUrl = "https://api.themoviedb.org/3/search/movie?api_key=$tmdbApiKey&query=" . urlencode($title);
    $result = getApiData($tmdbUrl);

    if (!empty($result['results'][0])) {
        $movieId = $result['results'][0]['id'];
        $detailsUrl = "https://api.themoviedb.org/3/movie/$movieId?api_key=$tmdbApiKey&append_to_response=images";
        $details = getApiData($detailsUrl);

        // Revisar si hay backdrop; si no, usa el poster como alternativa
        $backdropPath = $details['images']['backdrops'][0]['file_path'] ?? $details['poster_path'] ?? null;
        $logoPath = $details['images']['logos'][0]['file_path'] ?? null;

        if ($backdropPath && $logoPath) {
            return [
                'backdrop' => "https://image.tmdb.org/t/p/w300" . $backdropPath, // Resolución w300 para backdrops/poster
                'logo' => "https://image.tmdb.org/t/p/w92" . $logoPath // Resolución w92 para logos
            ];
        }
    }
    return null;
}

// URL de la API de Xtream Codes para obtener películas y series recientes
$moviesApiUrl = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_vod_streams";
$seriesApiUrl = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_series";

// Obtener los primeros 50 elementos y seleccionar los primeros 5 con backdrops o posters y logos completos
function getRecentItemsWithBackdrops($items) {
    $itemsWithBackdrops = [];
    foreach ($items as $item) {
        $images = getTMDbImages($item["name"]);
        if ($images) {
            $itemsWithBackdrops[] = [
                'title' => $item["name"],
                'play_link' => isset($item['stream_id']) ? "Movie_description.php?id=" . $item["stream_id"] : "series_description.php?id=" . $item["series_id"],
                'backdrop' => $images['backdrop'],
                'logo' => $images['logo']
            ];
            if (count($itemsWithBackdrops) >= 5) break; // Finaliza cuando se alcanzan 5 elementos
        }
    }
    return $itemsWithBackdrops;
}

// Obtener solo las primeras 5 películas y series recientes con backdrop o poster y logo
$movies = getApiData($moviesApiUrl);
$series = getApiData($seriesApiUrl);

$moviesWithBackdrops = getRecentItemsWithBackdrops(array_slice($movies, 0, 50));
$seriesWithBackdrops = getRecentItemsWithBackdrops(array_slice($series, 0, 50));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Películas y Series</title>
    <style>
        body {
            background-color: #141414;
            color: white;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .section-title {
            font-size: 24px;
            color: #e50914;
            margin: 20px;
            text-align: center;
        }
        .horizontal-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            padding: 20px;
            overflow-x: auto;
        }
        .item {
            position: relative;
            min-width: 300px;
            height: 200px;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            overflow: hidden;
        }
        .item img.logo {
            max-height: 40px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 2;
        }
    </style>
</head>
<body>

<h2 class="section-title">Películas Recientes</h2>
<div class="horizontal-container">
    <?php if (!empty($moviesWithBackdrops)): ?>
        <?php foreach ($moviesWithBackdrops as $movie): ?>
            <div class="item" style="background-image: url('<?php echo $movie['backdrop']; ?>');">
                <a href="<?php echo $movie['play_link']; ?>">
                    <img class="logo" src="<?php echo $movie['logo']; ?>" alt="<?php echo $movie['title']; ?>">
                </a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No hay películas disponibles en este momento.</p>
    <?php endif; ?>
</div>

<h2 class="section-title">Series Recientes</h2>
<div class="horizontal-container">
    <?php if (!empty($seriesWithBackdrops)): ?>
        <?php foreach ($seriesWithBackdrops as $serie): ?>
            <div class="item" style="background-image: url('<?php echo $serie['backdrop']; ?>');">
                <a href="<?php echo $serie['play_link']; ?>">
                    <img class="logo" src="<?php echo $serie['logo']; ?>" alt="<?php echo $serie['title']; ?>">
                </a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No hay series disponibles en este momento.</p>
    <?php endif; ?>
</div>

</body>
</html>
