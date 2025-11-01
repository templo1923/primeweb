<?php

include "session.php";
$pagename = "Movies";
include "header.php";
include "config.php";

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

// Obtenemos el parámetro de orden de la URL (si está presente)
$order = isset($_GET['order']) ? $_GET['order'] : 'default';

$id = $_GET["id"];

// Obtenemos el nombre de la categoría
$categories_api = getApiData($get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_vod_categories");

$category_name = "Unknown Category"; // Valor por defecto en caso de que no se encuentre el nombre
foreach ($categories_api as $category) {
    if ($category['category_id'] == $id) {
        $category_name = $category['category_name'];
        break;
    }
}

// Contenedor principal
echo "<div class='content-container'>\n";

// Sección introductoria con el nombre de la categoría
echo "<div class='header-section'>\n
        <h4 class='heading'>Movies on Demand - " . htmlspecialchars($category_name) . "</h4>\n
        <p class='description'>Browse through our collection of movies in the category: " . htmlspecialchars($category_name) . ". Whatever you're looking for, we have it right here for you!</p>\n
        <img class='header-image' src='assets/images/img42.png'>\n
      </div>\n";

// Menú de selección de orden moderno
echo "<div class='sort-container'>\n
        <label for='order-select' class='sort-label'>Sort by: </label>\n
        <div class='custom-select'>\n
            <select id='order-select' onchange='sortMovies()'>\n
                <option value='default'" . ($order == 'default' ? ' selected' : '') . ">Default</option>\n
                <option value='recent'" . ($order == 'recent' ? ' selected' : '') . ">Recently Added</option>\n
                <option value='az'" . ($order == 'az' ? ' selected' : '') . ">A-Z</option>\n
            </select>\n
        </div>\n
      </div>\n";

echo "<div class='movie-list'>\n";

// Obtenemos la lista de películas
$movie_api = getApiData($get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_vod_streams&category_id=" . $id);

// Ordenamos según el criterio seleccionado
if ($order == 'recent') {
    // Recientemente agregadas
    usort($movie_api, function($a, $b) {
        return strtotime($b['added']) - strtotime($a['added']);
    });
} elseif ($order == 'az') {
    // Orden alfabético
    usort($movie_api, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
}

// Mostramos las películas
foreach ($movie_api as $key2 => $value2) {
    $title2 = $value2["name"];
    $stream_id2 = $value2["stream_id"];
    $medialink2 = "Movie_description.php?id=" . $stream_id2;
    $img2 = $value2["stream_icon"];
    
    if (empty($img2) || $img2 == "https://image.tmdb.org/t/p/w600_and_h900_bestv2") {
        $poster = "https://i.imgur.com/Mn7aXQD.jpg";
    } else {
        $poster = $img2;
    }

    // Añadimos el título de la película bajo la imagen
    echo "<div class='movie-item'>\n\t
    <a href='" . $medialink2 . "'><img class='movie-poster' src='" . $poster . "'></a>\n\t
    <p class='movie-title'>" . $title2 . "</p>\n</div>";
}

echo "</div>\n</div>\n\n";
include "footer.php";

?>

<script>
// Función para redirigir según el orden seleccionado
function sortMovies() {
    var order = document.getElementById('order-select').value;
    window.location.href = "?id=<?php echo $id; ?>&order=" + order;
}
</script>

<style>
/* Estilo general inspirado en Amazon Prime Video */
body {
    background-color: #141414;
    color: #E1E9F1;
    font-family: Arial, sans-serif;
}

/* Contenedor general */
.content-container {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

/* Sección de encabezado */
.header-section {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 30px;
}

.heading {
    font-size: 28px;
    font-weight: bold;
    color: #E1E9F1;
}

.description {
    max-width: 600px;
    font-size: 16px;
    color: #9AA5B1;
}

.header-image {
    max-width: 300px;
    border-radius: 10px;
}

/* Menú de selección de orden */
.sort-container {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}

.sort-label {
    font-size: 16px;
    font-weight: bold;
    margin-right: 10px;
    color: #E1E9F1;
}

/* Estilos personalizados para el menú desplegable */
.custom-select {
    position: relative;
    width: 200px;
}

.custom-select::after {
    content: "\25BC"; /* Icono de flecha hacia abajo */
    position: absolute;
    top: 12px;
    right: 10px;
    font-size: 16px;
    pointer-events: none;
    color: #E1E9F1;
}

.custom-select select {
    width: 100%;
    padding: 10px;
    font-size: 16px;
    color: #0F171E;
    background-color: #E1E9F1;
    border: 1px solid #ccc;
    border-radius: 5px;
    appearance: none; /* Eliminamos la flecha predeterminada */
}

.custom-select select:focus {
    outline: none;
    box-shadow: 0 0 5px #007bff;
}

/* Lista de películas */
.movie-list {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: flex-start;
}

.movie-item {
    width: 160px;
    text-align: center;
    background-color: #1F2C3D;
    border-radius: 10px;
    padding: 10px;
    transition: transform 0.3s ease, background-color 0.3s ease;
}

.movie-item:hover {
    transform: scale(1.05);
    background-color: #263343;
}

.movie-poster {
    width: 100%;
    border-radius: 10px;
    height: auto;
    transition: transform 0.3s ease;
}

.movie-poster:hover {
    transform: scale(1.1);
}

.movie-title {
    font-size: 14px;
    color: #E1E9F1;
    margin-top: 10px;
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
}
</style>
