<?php
include "session.php";
include "config.php";

// Definir el User-Agent
$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36';

// Función para realizar una llamada CURL y devolver los datos
function makeApiCall($url, $userAgent) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Evitar verificación de certificado SSL
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Evitar verificación del host SSL

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        echo 'Error de CURL: ' . curl_error($ch);
        curl_close($ch);
        return [];
    }

    curl_close($ch);

    if ($httpCode !== 200) {
        echo "Error: No se pudo obtener la información de la API. Código HTTP: " . $httpCode;
        return [];
    }

    return json_decode($response, true);
}

// Función para obtener películas
function getMovies($username, $password, $get_dns, $userAgent) {
    $url = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_vod_streams";
    return makeApiCall($url, $userAgent);
}

// Función para obtener series
function getSeries($username, $password, $get_dns, $userAgent) {
    $url = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_series";
    return makeApiCall($url, $userAgent);
}

// Función para buscar en películas y series
function searchContent($search_query, $username, $password, $get_dns, $userAgent) {
    $movie_results = [];
    $series_results = [];
    
    $movies_api = getMovies($username, $password, $get_dns, $userAgent);
    $series_api = getSeries($username, $password, $get_dns, $userAgent);

    // Buscar coincidencias en películas
    foreach ($movies_api as $movie) {
        if (stripos($movie['name'], $search_query) !== false) {
            $movie_results[] = [
                'title' => htmlspecialchars($movie['name']),
                'id' => htmlspecialchars($movie['stream_id']),
                'poster' => filter_var($movie['stream_icon'], FILTER_VALIDATE_URL) ? $movie['stream_icon'] : "https://i.imgur.com/Mn7aXQD.jpg"
            ];
        }
    }

    // Buscar coincidencias en series
    foreach ($series_api as $series) {
        if (stripos($series['name'], $search_query) !== false) {
            $series_results[] = [
                'title' => htmlspecialchars($series['name']),
                'id' => htmlspecialchars($series['series_id']),
                'poster' => filter_var($series['cover'], FILTER_VALIDATE_URL) ? $series['cover'] : "https://i.imgur.com/Mn7aXQD.jpg"
            ];
        }
    }

    return ['movies' => $movie_results, 'series' => $series_results];
}

// Verificar si hay un término de búsqueda en la solicitud
$search_query = '';
$search_results = [];

if (isset($_GET['q']) && !empty($_GET['q'])) {
    $search_query = $_GET['q'];
    $search_results = searchContent($search_query, $username, $password, $get_dns, $userAgent);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WEBPLAYER</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #141b29;
            color: #fff;
            font-family: 'Roboto', sans-serif;
            margin: 0;
            transition: padding-left 0.5s ease;
            padding-left: 80px;
        }

        /* Menú vertical en el lado izquierdo */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 80px;
            height: 100%;
            background-color: #0f171e;
            color: white;
            padding-top: 20px;
            transition: width 0.5s ease;
            z-index: 1000;
            overflow: hidden;
        }

        .sidebar.expanded {
            width: 180px;
        }

        .sidebar .logo {
            text-align: center;
            padding-bottom: 20px;
        }

        .sidebar .logo img {
            height: 20px;
            transition: height 0.5s ease;
        }

        .sidebar.expanded .logo img {
            height: 50px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 15px;
            text-decoration: none;
            color: white;
            font-size: 18px;
            transition: background-color 0.3s ease;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar a:hover {
            background-color: #575757;
        }

        .sidebar i {
            font-size: 24px;
            margin-right: 20px;
        }

        .sidebar span {
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar.expanded span {
            opacity: 1;
        }

        .search-icon {
            display: flex;
            align-items: center;
            padding: 15px;
            color: #fff;
            cursor: pointer;
        }

        .search-container {
            display: none;
            padding: 10px;
        }

        .search-container input {
            padding: 8px;
            font-size: 14px;
            border-radius: 20px;
            border: none;
            background-color: #333;
            color: #fff;
            width: 150px;
            transition: width 0.3s ease;
        }

        .search-results {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .result-item {
            background-color: #1f2a44;
            border-radius: 10px;
            text-align: center;
            color: #fff;
        }

        .result-item img {
            width: 100%;
            height: 300px;
            border-radius: 10px 10px 0 0;
        }

        .section-title {
            font-size: 28px;
            text-align: center;
            color: #00a8e1;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<!-- Menú lateral -->
<div class="sidebar" id="sidebar">
    <div class="logo">
        <a href="homex.php">
            <img src="assets/img/logo.png" alt="Logo">
        </a>
    </div>

    <a class="search-icon" id="search-icon">
        <i class="fas fa-search"></i><span>Search</span>
    </a>

    <div class="search-container" id="search-container">
        <form id="search-form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="GET">
            <input type="text" placeholder="Search..." name="q" value="<?php echo htmlspecialchars($search_query); ?>" required>
        </form>
    </div>

    <a href="homex.php"><i class="fas fa-home"></i><span>Home</span></a>
    <a href="moviesondemand.php"><i class="fas fa-film"></i><span>Movies</span></a>
    <a href="seriesondemand.php"><i class="fa fa-video-camera"></i><span>Series</span></a>
    <a href="tvguide.php"><i class="fas fa-tv"></i><span>LiveTV</span></a>
    <a href="sportsschedule.php"><i class="fas fa-futbol"></i><span>Sports</span></a>
    <a href="myaccount.php"><i class="fas fa-user"></i><span>Account</span></a>
    <a href="settings.php"><i class="fas fa-cog"></i><span>Settings</span></a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
</div>

<?php if (!empty($search_query)): ?>
    <h1 class="section-title">Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h1>

    <?php if (!empty($search_results['movies'])): ?>
        <h2 class="section-title">Movies</h2>
        <div class="search-results">
            <?php foreach ($search_results['movies'] as $movie): ?>
                <div class="result-item">
                    <a href="Movie_description.php?id=<?php echo $movie['id']; ?>">
                        <img src="<?php echo $movie['poster']; ?>" alt="<?php echo $movie['title']; ?>">
                        <p><?php echo $movie['title']; ?></p>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($search_results['series'])): ?>
        <h2 class="section-title">Series</h2>
        <div class="search-results">
            <?php foreach ($search_results['series'] as $series): ?>
                <div class="result-item">
                    <a href="seriesvideo.php?id=<?php echo $series['id']; ?>">
                        <img src="<?php echo $series['poster']; ?>" alt="<?php echo $series['title']; ?>">
                        <p><?php echo $series['title']; ?></p>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($search_results['movies']) && empty($search_results['series'])): ?>
        <p class="section-title">No results found for "<?php echo htmlspecialchars($search_query); ?>"</p>
    <?php endif; ?>
<?php endif; ?>

<!-- JavaScript para el comportamiento del menú y la búsqueda -->
<script>
    const sidebar = document.getElementById('sidebar');
    const searchIcon = document.getElementById('search-icon');
    const searchContainer = document.getElementById('search-container');
    let sidebarVisible = false;

    document.addEventListener('DOMContentLoaded', function() {
        document.body.style.paddingLeft = "80px";
    });

    sidebar.addEventListener('mouseover', function() {
        sidebar.classList.add('expanded');
        document.body.style.paddingLeft = "180px";
        sidebarVisible = true;
    });

    sidebar.addEventListener('mouseleave', function() {
        sidebarVisible = false;
        setTimeout(function() {
            if (!sidebarVisible) {
                sidebar.classList.remove('expanded');
                document.body.style.paddingLeft = "80px";
            }
        }, 3000);
    });

    searchIcon.addEventListener('click', function() {
        if (searchContainer.style.display === 'none' || searchContainer.style.display === '') {
            searchContainer.style.display = 'block';
        } else {
            searchContainer.style.display = 'none';
        }
    });
</script>

</body>
</html>
