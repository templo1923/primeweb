<?php
include "session.php";
include "config.php";
include "header.php";

// Función para obtener datos de la API con caché en memoria y CURL
function getApiDataWithCache($apiUrl, $cacheKey, $expirationTime = 300) {
    if (isset($_SESSION[$cacheKey]) && (time() - $_SESSION[$cacheKey]['time'] < $expirationTime)) {
        return $_SESSION[$cacheKey]['data'];
    }

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

    $data = json_decode($response, true);
    $_SESSION[$cacheKey] = [
        'time' => time(),
        'data' => $data
    ];
    return $data;
}

// Obtener categorías de películas
$apiUrl = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_vod_categories";
$movie_categories = getApiDataWithCache($apiUrl, 'movie_categories_cache');

// Función para buscar enlaces de categorías por palabras clave
function getCategoryLink($categories, $keywords) {
    foreach ($categories as $category) {
        $category_name = strtolower($category["category_name"]);
        foreach ($keywords as $keyword) {
            if (strpos($category_name, $keyword) !== false) {
                return "movies.php?id=" . $category["category_id"];
            }
        }
    }
    return "#"; // Enlace predeterminado si no se encuentra la categoría
}

// Configuración de los enlaces de categorías por palabras clave en español e inglés
$links = [
    'max' => getCategoryLink($movie_categories, ['hbo', 'max']),
    'disney' => getCategoryLink($movie_categories, ['disney']),
    'netflix' => getCategoryLink($movie_categories, ['netflix']),
    'amazon' => getCategoryLink($movie_categories, ['amazon', 'prime']),
    'marvel' => getCategoryLink($movie_categories, ['marvel']),
    'xmas' => getCategoryLink($movie_categories, ['navidad', 'xmas']),
    'halloween' => getCategoryLink($movie_categories, ['terror', 'horror'])
];

// Obtener películas
$apiUrl = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_vod_streams";
$channel_api = getApiDataWithCache($apiUrl, 'vod_streams_cache');
shuffle($channel_api);

// Preparar datos para las películas destacadas
$random_movies_info = [];
$max_movies = 5;
$movie_count = 0;

foreach ($channel_api as $movie) {
    if ($movie_count >= $max_movies) break;

    $id = $movie["stream_id"];
    $infoApiUrl = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_vod_info&vod_id=" . $id;
    $channel_api_info = getApiDataWithCache($infoApiUrl, 'vod_info_cache_' . $id, 3600);

    $channel_api_info2 = $channel_api_info["info"];

    if (isset($channel_api_info2["backdrop_path"][0]) && !empty($channel_api_info2["backdrop_path"][0])) {
        $random_movies_info[] = [
            'title' => $channel_api_info2["name"],
            'description' => $channel_api_info2["plot"],
            'rating' => $channel_api_info2["rating"],
            'background_image' => $channel_api_info2["backdrop_path"][0],
            'play_link' => "Movie_description?id=" . $id
        ];
        $movie_count++;
    }
}

// Obtener series
$seriesApiUrl = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_series";
$channel_api1 = getApiDataWithCache($seriesApiUrl, 'series_cache');
shuffle($channel_api1);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - IPTV Estilo Netflix</title>
    <style>
        body {
            background-color: #141414;
            color: white;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .focusable:focus {
            outline: 3px solid #e50914;
        }
        .featured-movie-container {
            position: relative;
            height: 60vh;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: flex-end;
            padding: 30px;
            box-shadow: inset 0 -100px 100px rgba(0, 0, 0, 0.6);
        }
        .featured-movie-info {
            max-width: 40%;
            padding: 20px;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 10px;
        }
        .featured-movie-info h2 {
            font-size: 48px;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.9);
        }
        .featured-movie-info p {
            font-size: 18px;
            margin-bottom: 5px;
        }
        .featured-movie-info .rating {
            font-size: 24px;
            color: #ffcc00;
        }
        .play-button {
            display: inline-block;
            background-color: #e50914;
            color: white;
            padding: 15px 25px;
            font-size: 20px;
            font-weight: bold;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 15px;
            transition: background-color 0.3s ease;
        }
        .play-button:hover {
            background-color: #f40612;
        }
        .section-title {
            margin: 50px 20px 10px;
            font-size: 24px;
            color: #e50914;
        }
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 20px;
        }
        .grid-item {
            transition: transform 0.3s ease;
        }
        .grid-item img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 10px;
        }
        .grid-item p {
            text-align: center;
            margin-top: 10px;
            font-size: 16px;
        }
        .grid-item:hover {
            transform: scale(1.05);
        }
        .banner-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }
        .banner-buttons img {
            width: 150px;
            cursor: pointer;
            transition: transform 0.3s;
        }
        .banner-buttons img:hover {
            transform: scale(1.1);
        }
    </style>
</head>
<body>

<div class="featured-movie-container focusable" id="featured-movie-container" tabindex="0">
    <div class="featured-movie-info">
        <h2 id="movie-title">Título de Película</h2>
        <p id="movie-description">Descripción de la película</p>
        <p class="rating" id="movie-rating">Calificación: 9/10</p>
        <a id="play-link" class="play-button focusable" href="#" tabindex="0">Ver ahora</a>
    </div>
</div>

<div class="banner-buttons">
    <?php if ($links['max'] !== "#"): ?>
        <a href="<?php echo $links['max']; ?>" tabindex="0" class="focusable"><img src="img/max.webp" alt="HBO Max"></a>
    <?php endif; ?>
    
    <?php if ($links['disney'] !== "#"): ?>
        <a href="<?php echo $links['disney']; ?>" tabindex="0" class="focusable"><img src="img/disney.webp" alt="Disney"></a>
    <?php endif; ?>
    
    <?php if ($links['netflix'] !== "#"): ?>
        <a href="<?php echo $links['netflix']; ?>" tabindex="0" class="focusable"><img src="img/netflix.webp" alt="Netflix"></a>
    <?php endif; ?>
    
    <?php if ($links['amazon'] !== "#"): ?>
        <a href="<?php echo $links['amazon']; ?>" tabindex="0" class="focusable"><img src="img/prime.webp" alt="Amazon"></a>
    <?php endif; ?>
    
    <?php if ($links['marvel'] !== "#"): ?>
        <a href="<?php echo $links['marvel']; ?>" tabindex="0" class="focusable"><img src="img/marvel.webp" alt="Marvel"></a>
    <?php endif; ?>
    
    <?php if ($links['xmas'] !== "#"): ?>
        <a href="<?php echo $links['xmas']; ?>" tabindex="0" class="focusable"><img src="img/xmas.webp" alt="Navidad"></a>
    <?php endif; ?>
    
    <?php if ($links['halloween'] !== "#"): ?>
        <a href="<?php echo $links['halloween']; ?>" tabindex="0" class="focusable"><img src="img/halloween.webp" alt="Halloween"></a>
    <?php endif; ?>
</div>

<h2 class="section-title">Series</h2>
<div class="grid-container">
    <?php
    $i = 0;
    foreach ($channel_api1 as $value) {
        if ($i < 16) {
            $title = $value["name"];
            $series_id = $value["series_id"];
            $medialink = "seriesvideo?id=" . $series_id;
            $poster = $value["cover"] ?? "https://i.imgur.com/Mn7aXQD.jpg";
            echo "<div class='grid-item focusable' tabindex='0'>
                    <a href='$medialink'><img src='$poster' alt='$title' loading='lazy'></a>
                    <p>$title</p>
                  </div>";
            $i++;
        }
    }
    ?>
</div>

<h2 class="section-title">Películas</h2>
<div class="grid-container">
    <?php
    $i = 0;
    foreach ($channel_api as $value) {
        if ($i < 16) {
            $title = $value["name"];
            $stream_id = $value["stream_id"];
            $medialink = "Movie_description?id=" . $stream_id;
            $poster = $value["stream_icon"] ?? "https://i.imgur.com/Mn7aXQD.jpg";
            echo "<div class='grid-item focusable' tabindex='0'>
                    <a href='$medialink'><img src='$poster' alt='$title' loading='lazy'></a>
                    <p>$title</p>
                  </div>";
            $i++;
        }
    }
    ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" defer></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const movies = <?php echo json_encode($random_movies_info); ?>;
        let movieIndex = 0;

        function updateFeaturedMovie() {
            const movie = movies[movieIndex];
            document.getElementById('movie-title').innerText = movie.title;
            document.getElementById('movie-description').innerText = movie.description;
            document.getElementById('movie-rating').innerText = 'Calificación: ' + (movie.rating || 'Sin calificación') + '/10';
            document.getElementById('featured-movie-container').style.backgroundImage = `url('${movie.background_image}')`;
            document.getElementById('play-link').href = movie.play_link;

            movieIndex = (movieIndex + 1) % movies.length;
        }

        setInterval(updateFeaturedMovie, 5000);
        updateFeaturedMovie();
    });

    document.addEventListener("keydown", function(event) {
        const focusableElements = document.querySelectorAll('.focusable');
        const columns = 4; // Ajusta el número de columnas en cada fila
        let index = Array.from(focusableElements).indexOf(document.activeElement);

        switch(event.key) {
            case "ArrowRight":
                if (index < focusableElements.length - 1) index++;
                focusableElements[index].focus();
                break;
            case "ArrowLeft":
                if (index > 0) index--;
                focusableElements[index].focus();
                break;
            case "ArrowDown":
                index = Math.min(index + columns, focusableElements.length - 1); 
                focusableElements[index].focus();
                break;
            case "ArrowUp":
                index = Math.max(index - columns, 0);
                focusableElements[index].focus();
                break;
            case "Enter":
                document.activeElement.click();
                break;
        }
    });
</script>

</body>
</html>
