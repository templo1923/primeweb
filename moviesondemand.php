<?php
include "session.php";
include "config.php";
include "header.php"; // Incluir el menú desde header.php

// Función para realizar una solicitud API usando CURL
function getApiData($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        curl_close($ch);
        return [];
    }
    curl_close($ch);
    return json_decode($response, true);
}

// Obtener todas las categorías de películas
$categoriesUrl = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_vod_categories";
$categories = getApiData($categoriesUrl);

// Añadir la categoría de "Recientemente Agregados" al inicio de la lista
array_unshift($categories, ['category_id' => 'recently_added', 'category_name' => 'Recientemente Agregados']);

// Seleccionar la primera categoría como predeterminada
$initialCategoryId = $categories[0]['category_id'];
$initialCategoryName = $categories[0]['category_name'];

// Obtener las películas para la categoría inicial
$initialMoviesUrl = $initialCategoryId === 'recently_added'
    ? $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_vod_streams&limit=25"
    : $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_vod_streams&category_id=" . $initialCategoryId . "&limit=6";
$carousel_movies = array_slice(getApiData($initialMoviesUrl), 0, 25);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - IPTV Estilo Prime</title>
    <style>
        body { background-color: #0f0f0f; color: white; font-family: 'Arial', sans-serif; margin: 0; padding: 0; overflow-x: hidden; }
        .container { max-width: auto; margin: 0 auto; padding: 10px; }

        /* Banner de película destacada */
        .featured-movie-banner {
            position: relative;
            height: 45vh;
            background-size: cover;
            background-position: top;
            display: flex;
            align-items: flex-end;
            padding: 30px;
            color: white;
            box-shadow: inset 0 -100px 100px rgba(0, 0, 0, 0.6);
            transition: background-image 0.5s ease-in-out;
        }
        .featured-movie-info {
            max-width: 50%;
            padding: 20px;
            background: rgba(0, 0, 0, 0.8);
            border-radius: 10px;
        }
        .featured-movie-info h2 { font-size: 36px; color: #00A8E1; text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.7); }
        .play-button {
            display: inline-block;
            background-color: #00A8E1;
            color: white;
            padding: 12px 25px;
            font-size: 18px;
            text-decoration: none;
            border-radius: 5px;
            transition: 0.3s;
        }
        .play-button:hover { background-color: #007EA7; }

        /* Botón y lista de categorías estilo Prime */
        .category-button {
            background-color: #00A8E1;
            color: white;
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            margin: 20px 0;
            position: relative;
            z-index: 999;
        }

        /* Título de la categoría actual */
        .category-title {
            font-size: 24px;
            color: #00A8E1;
            margin: 20px 0;
        }

        /* Lista desplegable de categorías */
        .category-list {
            display: none;
            background-color: #1C1C1E;
            border: 2px solid #00A8E1;
            border-radius: 5px;
            padding: 10px;
            position: absolute;
            max-height: 200px;
            overflow-y: auto;
            width: 350px;
        }

        .category-item {
            padding: 10px;
            font-size: 16px;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .category-item:hover {
            background-color: #333;
        }

        /* Estilo personalizado para la barra de desplazamiento */
        .category-list::-webkit-scrollbar {
            width: 8px;
        }

        .category-list::-webkit-scrollbar-track {
            background: #333;
        }

        .category-list::-webkit-scrollbar-thumb {
            background-color: #00A8E1;
            border-radius: 5px;
        }

        /* Grid de películas por categoría */
        .movies-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 20px; }
        .poster-item img { width: 100%; height: auto; border-radius: 10px; transition: transform 0.3s; }
    </style>
</head>
<body>

<div class="container">
    <!-- Banner de película destacada con poster -->
    <div id="featuredMovieBanner" class="featured-movie-banner" style="background-image: url('<?php echo $carousel_movies[0]['stream_icon'] ?? "https://i.imgur.com/Mn7aXQD.jpg"; ?>');">
        <div class="featured-movie-info">
            <h2 id="movieTitle"><?php echo $carousel_movies[0]['name']; ?></h2>
            <a id="playLink" href="Movie_description?id=<?php echo $carousel_movies[0]['stream_id']; ?>" class="play-button">Ver Ahora</a>
        </div>
    </div>

    <!-- Título de la categoría seleccionada -->
    <h2 id="currentCategory" class="category-title"> <?php echo htmlspecialchars($initialCategoryName); ?></h2>

    <!-- Botón y lista desplegable de categorías -->
    <button class="category-button" onclick="toggleCategoryList()">Categoría</button>
    
    <!-- Lista de categorías sin modal -->
    <div id="categoryList" class="category-list">
        <?php foreach ($categories as $category): ?>
            <div class="category-item" onclick="selectCategory('<?php echo $category['category_id']; ?>', '<?php echo htmlspecialchars($category['category_name']); ?>')">
                <?php echo $category['category_name']; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Contenedor para mostrar las películas de la categoría seleccionada -->
    <div id="movies-container" class="movies-grid"></div>
</div>

<script>
    // Función para mostrar u ocultar la lista de categorías
    function toggleCategoryList() {
        const categoryList = document.getElementById("categoryList");
        categoryList.style.display = categoryList.style.display === "none" ? "block" : "none";
    }

    // Configuración para el carrusel de películas destacadas
    let carouselMovies = <?php echo json_encode($carousel_movies); ?>;
    let currentMovieIndex = 0;

    function updateFeaturedMovie() {
        const movie = carouselMovies[currentMovieIndex];
        document.getElementById('featuredMovieBanner').style.backgroundImage = `url('${movie.stream_icon || "https://i.imgur.com/Mn7aXQD.jpg"}')`;
        document.getElementById('movieTitle').innerText = movie.name;
        document.getElementById('playLink').href = "Movie_description?id=" + movie.stream_id;

        // Avanzar al siguiente índice (reiniciar al llegar al final)
        currentMovieIndex = (currentMovieIndex + 1) % carouselMovies.length;
    }

    // Iniciar el carrusel cada 5 segundos
    setInterval(updateFeaturedMovie, 5000);

// Función para cargar películas de la categoría seleccionada y actualizar el título de la categoría
function selectCategory(categoryId, categoryName) {
    const moviesContainer = document.getElementById('movies-container');
    document.getElementById("categoryList").style.display = "none"; // Ocultar la lista después de seleccionar
    document.getElementById("currentCategory").innerText = " " + categoryName; // Actualizar el título de la categoría

    // Construir URL para categoría específica o mostrar 25 para "Recientemente Agregados"
    const url = categoryId === 'recently_added'
        ? '<?php echo $get_dns; ?>/player_api.php?username=<?php echo $username; ?>&password=<?php echo $password; ?>&action=get_vod_streams&limit=25'
        : '<?php echo $get_dns; ?>/player_api.php?username=<?php echo $username; ?>&password=<?php echo $password; ?>&action=get_vod_streams&category_id=' + categoryId + '&limit=6';

    // Realizar una solicitud AJAX para obtener las películas de la categoría seleccionada
    fetch(url)
        .then(response => response.json())
        .then(movies => {
            // Ordenar las películas por fecha de adición en orden descendente, si el campo "added" está disponible
            movies.sort((a, b) => (b.added || 0) - (a.added || 0));

            // Limitar a 6 películas para el carrusel
            carouselMovies = movies.slice(0, 6);
            currentMovieIndex = 0; // Reiniciar el índice del carrusel
            updateFeaturedMovie(); // Actualizar la primera película del carrusel

            // Renderizar las películas ordenadas
            let htmlContent = '';
            movies.forEach(movie => {
                const title = movie.name;
                const streamId = movie.stream_id;
                const poster = movie.stream_icon || "https://i.imgur.com/Mn7aXQD.jpg";
                const medialink = "Movie_description?id=" + streamId;

                htmlContent += `
                    <div class="poster-item">
                        <a href="${medialink}"><img src="${poster}" alt="${title}" loading="lazy"></a>
                        <p>${title}</p>
                    </div>
                `;
            });
            moviesContainer.innerHTML = htmlContent;
        });
}


    // Cargar películas de la primera categoría al cargar la página
    document.addEventListener("DOMContentLoaded", function() {
        selectCategory("<?php echo $initialCategoryId; ?>", "<?php echo htmlspecialchars($initialCategoryName); ?>");
    });
</script>

</body>
</html>
