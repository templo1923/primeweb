<?php
include "session.php";
include "config.php";
include "header.php"; // Incluir el menú desde header.php

// Definir el User-Agent
$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36';

// Función para realizar una solicitud API usando CURL
function getApiData($url) {
    global $userAgent;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Deshabilitar la verificación SSL
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

// Obtener las series agregadas recientemente desde la API
$recent_series_url = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_series&limit=10";
$recent_series = getApiData($recent_series_url);

// Obtener las categorías de series desde la API
$categories_url = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_series_categories";
$channel_api = getApiData($categories_url);

// Limitar la carga inicial a las primeras 5 secciones
$initial_sections = array_slice($channel_api, 0, 5);
$later_sections = array_slice($channel_api, 5);

// Obtener series desde la API de Xtream Codes y mezclarlas para las series destacadas
$all_series_url = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_series";
$channel_api_series = getApiData($all_series_url);
shuffle($channel_api_series); // Mezclar series para que sean aleatorias

// Preparar datos para las series destacadas
$random_series_info = [];
$max_series = 5; // Número máximo de series a mostrar
$series_count = 0; // Contador de series añadidas

foreach ($channel_api_series as $series) {
    if ($series_count >= $max_series) {
        break; // Detener el bucle cuando se hayan añadido 5 series
    }

    $id = $series["series_id"];
    $series_info_url = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_series_info&series_id=" . $id;
    $series_api_info = getApiData($series_info_url);

    // Verificar si la serie tiene un backdrop
    if (isset($series_api_info["info"]["backdrop_path"][0]) && !empty($series_api_info["info"]["backdrop_path"][0])) {
        $backdrop = $series_api_info["info"]["backdrop_path"][0];
        $background_image = $backdrop;
        $play_link = "seriesvideo?id=" . $id; // Enlace para el botón de play

        // Guardar la información de la serie con backdrop
        $random_series_info[] = [
            'title' => $series_api_info["info"]["name"],
            'description' => $series_api_info["info"]["plot"],
            'rating' => $series_api_info["info"]["rating"],
            'background_image' => $background_image,
            'play_link' => $play_link // Guardar el enlace para el botón de play
        ];

        // Incrementar el contador de series añadidas
        $series_count++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - IPTV Style Netflix (TV Series)</title>
    <style>
        body {
            background-color: #1c1c1c;
            color: white;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        /* Contenedor de la serie destacada */
        .featured-series-container {
            position: relative;
            height: 60vh;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display: flex;
            align-items: flex-end;
            padding: 30px;
            color: white;
            box-shadow: inset 0 -100px 100px rgba(0, 0, 0, 0.6);
        }

        .featured-series-info {
            max-width: 40%;
            padding: 20px;
            border-radius: 10px;
        }

        .featured-series-info h2 {
            font-size: 48px;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.9);
        }

        .featured-series-info p {
            font-size: 18px;
            margin-bottom: 5px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.9);
        }

        .featured-series-info .rating {
            font-size: 24px;
            color: #ffcc00;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.9);
        }

        /* Botón de reproducción estilo Netflix */
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

        /* Carrusel de contenido */
        .poster-carousel {
            display: flex;
            overflow-x: auto;
            padding: 20px 0;
            scroll-behavior: smooth;
        }

        .poster-carousel::-webkit-scrollbar {
            display: none;
        }

        .poster-item {
            flex: 0 0 auto;
            width: 150px;
            margin-right: 10px;
            text-align: center;
        }

        .poster-item img {
            width: 100%;
            height: auto;
            border-radius: 10px;
            transition: transform 0.3s ease-in-out;
        }

        .poster-item img:hover {
            transform: scale(1.1);
        }

        .poster-item p {
            margin-top: 10px;
            font-size: 14px;
            color: #e5e5e5;
        }

        /* Estilo de las categorías no encontradas */
        .no-item-found {
            color: #888;
            font-style: italic;
            text-align: center;
            margin: 50px 0;
        }

        .category-section .view-all a {
            color: red !important;
            text-decoration: none; /* Opcional: remover subrayado */
        }

        /* Cambiar el color del enlace "View All Series" cuando se pasa el cursor */
        .category-section .view-all a:hover {
            color: darkred !important;
        }

        /* Responsivo */
        @media (max-width: 768px) {
            .poster-item {
                width: 120px;
            }

            .title {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>

<!-- Contenedor de la serie destacada -->
<div class="featured-series-container" id="featured-series-container">
    <div class="featured-series-info">
        <h2 id="series-title">Series Title</h2>
        <p id="series-description">Series Description</p>
        <p class="rating" id="series-rating">Rating: 9/10</p>
        <a id="play-link" class="play-button" href="#">Play</a>
    </div>
</div>

<!-- Sección de Recently Added -->
<h2 class="section-title">TV Series On Demand</h2>
<div class="category-section">
    <div class="section-header">
        <h1 class="title">Recently Added</h1>
        <div class="view-all"><a href="series.php?id=recently_added">View All Recently Added</a></div>
    </div>
    <div class="poster-carousel">
        <?php
        foreach ($recent_series as $series) {
            $title = $series["name"];
            $stream_id = $series["series_id"];
            $poster = !empty($series["cover"]) ? $series["cover"] : "https://i.imgur.com/Mn7aXQD.jpg";
            $medialink = "seriesvideo?id=" . $stream_id;
            echo "<div class='poster-item'>";
            echo "<a href='$medialink'><img src='$poster' alt='$title' loading='lazy'></a>";
            echo "<p>$title</p>";
            echo "</div>";
        }
        ?>
    </div>
</div>

<!-- Sección de categorías de series (inicialmente 5) -->
<?php
foreach ($initial_sections as $value) {
    $category_id = $value["category_id"];
    $category_name = $value["category_name"];

    echo "<div class='category-section'>\n";
    echo "<div class='section-header'>\n";
    echo "<h1 class='title'>$category_name</h1>\n";
    echo "<div class='view-all'><a href='series.php?id=$category_id'>View All Series</a></div>\n";
    echo "</div>\n";

    // Obtener los primeros 16 pósters de cada categoría de series
    $series_api_url = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_series&category_id=" . $category_id . "&limit=16";
    $series_api = getApiData($series_api_url);

    // Crear carrusel horizontal para las series
    echo "<div class='poster-carousel'>\n";

    foreach ($series_api as $series) {
        $title = $series["name"];
        $stream_id = $series["series_id"];
        $poster = !empty($series["cover"]) ? $series["cover"] : "https://i.imgur.com/Mn7aXQD.jpg";
        $medialink = "seriesvideo?id=" . $stream_id;

        echo "<div class='poster-item'>\n";
        echo "<a href='$medialink'><img src='$poster' alt='$title' loading='lazy'></a>\n";
        echo "<p>$title</p>\n";
        echo "</div>\n";
    }

    echo "</div>\n"; // Fin del carrusel
    echo "</div>\n"; // Fin de la sección de categoría
}
?>

<!-- Secciones adicionales que se cargan bajo demanda -->
<div id="lazy-sections"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

<script>
    const series = <?php echo json_encode($random_series_info); ?>;
    let seriesIndex = 0;

    // Función para actualizar la serie destacada
    function updateFeaturedSeries() {
        const currentSeries = series[seriesIndex];
        document.getElementById('series-title').innerText = currentSeries.title;
        document.getElementById('series-description').innerText = currentSeries.description;
        document.getElementById('series-rating').innerText = 'Rating: ' + (currentSeries.rating || 'Not rated') + '/10';
        document.getElementById('featured-series-container').style.backgroundImage = `url('${currentSeries.background_image}')`;
        document.getElementById('play-link').href = currentSeries.play_link; // Actualizar el enlace del botón de play

        // Incrementar el índice y reiniciar si llega al final
        seriesIndex = (seriesIndex + 1) % series.length;
    }

    // Actualizar la serie destacada cada 5 segundos
    setInterval(updateFeaturedSeries, 5000);

    // Mostrar la primera serie inmediatamente al cargar la página
    document.addEventListener("DOMContentLoaded", function() {
        updateFeaturedSeries();
    });
</script>

<!-- JavaScript para cargar más secciones al hacer scroll -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    let laterSections = <?php echo json_encode($later_sections); ?>;
    let lazySectionsContainer = document.getElementById('lazy-sections');
    let currentIndex = 0;

    // Función para cargar las siguientes secciones
    function loadMoreSections() {
        for (let i = 0; i < 2; i++) { // Cargar 2 secciones adicionales a la vez
            if (currentIndex >= laterSections.length) {
                return; // Si no hay más secciones, terminar
            }

            let section = laterSections[currentIndex];
            let category_id = section.category_id;
            let category_name = section.category_name;

            let sectionHTML = `
                <div class="category-section">
                    <div class="section-header">
                        <h1 class="title">` + category_name + `</h1>
                        <div class="view-all"><a href="series.php?id=` + category_id + `">View All Series</a></div>
                    </div>
                    <div class="poster-carousel" id="carousel_` + category_id + `">
                    </div>
                </div>
            `;

            lazySectionsContainer.insertAdjacentHTML('beforeend', sectionHTML);

            // Cargar las series para esta categoría
            fetch('<?php echo $get_dns; ?>/player_api.php?username=<?php echo $username; ?>&password=<?php echo $password; ?>&action=get_series&category_id=' + category_id + '&limit=16')
                .then(response => response.json())
                .then(series_api => {
                    let carousel = document.getElementById('carousel_' + category_id);
                    series_api.forEach(series => {
                        let title = series.name;
                        let stream_id = series.series_id;
                        let poster = series.cover || "https://via.placeholder.com/150x225.png?text=No+Image";
                        let medialink = "seriesvideo?id=" + stream_id;

                        let seriesHTML = `
                            <div class="poster-item">
                                <a href="` + medialink + `"><img src="` + poster + `" alt="` + title + `" loading="lazy"></a>
                                <p>` + title + `</p>
                            </div>
                        `;
                        carousel.insertAdjacentHTML('beforeend', seriesHTML);
                    });
                });

            currentIndex++;
        }
    }

    // Cargar las primeras 2 secciones al inicio
    loadMoreSections();

    // Cargar más secciones cuando el usuario hace scroll hacia abajo
    window.addEventListener('scroll', function() {
        if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 100) {
            loadMoreSections();
        }
    });
});
</script>

</body>
</html>
