<?php
// Incluir archivos necesarios
include "session.php";
include "header.php";
include "includes/functions.php"; // Asegúrate de que este archivo contiene las funciones necesarias
include "config.php";

// Función para obtener datos desde Xtream Codes API usando CURL
function getXtreamData($endpoint, $params = []) {
    global $get_dns, $username, $password;
    $url = $get_dns . "/player_api.php?username=" . urlencode($username) . "&password=" . urlencode($password) . "&action=" . urlencode($endpoint);
    if (!empty($params)) {
        $url .= '&' . http_build_query($params);
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
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

// Detectar y establecer la zona horaria del usuario
if (isset($_COOKIE['user_timezone'])) {
    $valid_timezones = timezone_identifiers_list();
    $user_timezone = $_COOKIE['user_timezone'];
    if (in_array($user_timezone, $valid_timezones)) {
        date_default_timezone_set($user_timezone);
    } else {
        date_default_timezone_set('UTC');
    }
} else {
    date_default_timezone_set('UTC');
    echo '
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                document.cookie = "user_timezone=" + timezone + "; path=/; max-age=" + (60*60*24*365);
                location.reload();
            });
        </script>
    ';
    exit();
}

$pagename = "TV Guide";
$logo_url = "assets/img/logo.png";

$id = isset($_GET["id"]) ? $_GET["id"] : null;
$slug = isset($_GET["slug"]) ? $_GET["slug"] : null;
$selectedCategoryId = isset($_GET['category_id']) ? $_GET['category_id'] : null;

// Lógica para reproducir canales en vivo
if ($slug == "live" && $id !== null) {
    if (isset($_COOKIE["settings_array"])) {
        $SettingArray = json_decode($_COOKIE["settings_array"], true);
        $setting_ext = isset($SettingArray["stream_type"]) ? $SettingArray["stream_type"] : "m3u8";
    } else {
        $setting_ext = "m3u8"; 
    }
    $video_url = $get_dns . "/live/" . urlencode($username) . "/" . urlencode($password) . "/" . urlencode($id) . "." . $setting_ext;
    $mime_type = "application/x-mpegURL";  

    // Obtener el nombre del canal en vivo
    $channel_info_list = getXtreamData('get_live_streams', ['stream_id' => $id]);
    $content_name = "Canal Desconocido"; 
    if (!empty($channel_info_list)) {
        foreach ($channel_info_list as $channel_info) {
            if ($channel_info['stream_id'] == $id) {
                $content_name = htmlspecialchars($channel_info['name'], ENT_QUOTES, 'UTF-8');  
                break;
            }
        }
    }
}

// Si se hace clic en una categoría, cargar los canales de esa categoría
if (isset($_GET['category_id']) && !isset($_GET['slug'])) {
    $category_id = $_GET['category_id'];

    // Obtener los canales de la categoría seleccionada usando CURL
    $channels = getXtreamData('get_live_streams', ['category_id' => $category_id]);

    foreach ($channels as $channel) {
        $stream_id = htmlspecialchars($channel["stream_id"], ENT_QUOTES, 'UTF-8');
        $title = htmlspecialchars($channel["name"], ENT_QUOTES, 'UTF-8');
        $desc_image = htmlspecialchars($channel["stream_icon"], ENT_QUOTES, 'UTF-8');

        echo "<div class='channel-item' onclick='playChannel({$stream_id})'>
                <img src='{$desc_image}' onerror=\"this.onerror=null;this.src='assets/img/logo.png';\" alt='{$title}'/>
                <p>{$title}</p>
              </div>";
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($sitename, ENT_QUOTES, 'UTF-8'); ?> - TV Guide</title>
    <link rel="icon" href="assets/img/favicon.ico" type="image/ico">
    
    <!-- Video.js -->
    <link href="https://vjs.zencdn.net/7.17.0/video-js.css" rel="stylesheet" />
    <script src="https://vjs.zencdn.net/7.17.0/video.min.js"></script>
    
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-pY1yV/hZPNENkVgXFTmwO1e2nHjAhEVHjS2TW2AqgXg1XeH5SPMkwK+6PPc5Yx9L6WZJYULDEf5KJ8h5VQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        body {
            background-color: #1a1a1a;
            color: #f1f1f1;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .smarters-layout {
            display: flex;
            height: 98vh;
            padding: 20px;
            gap: 20px;
            margin-left: 45px; 
        }

        .video-player-column {
            width: 40%; 
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .video-player-container {
            width: 100%;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        video {
            width: 100%;
            height: auto;
        }

        .video-info {
            position: absolute;
            top: 5px;
            left: 60px;
            color: white;
            font-size: 18px;
            z-index: 2;
        }

        .video-player-logo {
            position: absolute;
            top: 10px;
            right: 20px;
            width: 100px;
            z-index: 2;
        }

        .back_button {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(0, 0, 0, 0.7);
            padding: 10px;
            cursor: pointer;
            border-radius: 50%;
            text-decoration: none;
            color: white;
            font-size: 16px;
            z-index: 5;
            transition: background 0.3s ease;
        }

        .back_button:hover {
            background: rgba(255, 0, 0, 0.8);
        }

        .category-section {
            width: 20%; 
            background: linear-gradient(135deg, #2c3e50, #4ca1af);
            padding: 35px;
            overflow-y: auto; 
            border-radius: 10px;
            box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.5);
            height: 87%;
        }

        .category-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .category-item {
            background: #333;
            color: white;
            padding: 15px;
            cursor: pointer;
            border-radius: 0px;
            transition: transform 0.3s ease, background 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        .category-item:hover {
            background: #1abc9c;
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.4);
        }

        .channel-section {
            width: 25%; 
            background: #1a1a1a;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.5);
            overflow-y: auto; 
            height: 92%;
        }

        .channel-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .channel-item {
            display: flex;
            align-items: center;
            background: #333;
            color: white;
            padding: 15px;
            cursor: pointer;
            border-radius: 10px;
            transition: transform 0.3s ease, background 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        .channel-item:hover {
            background: #3498db;
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
        }

        .channel-item img {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            margin-right: 15px;
            object-fit: cover;
        }

        .no-epg {
            color: #f1f1f1;
            text-align: center;
        }

        .category-section::-webkit-scrollbar,
        .channel-section::-webkit-scrollbar {
            width: 8px;
        }

        .category-section::-webkit-scrollbar-thumb,
        .channel-section::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.3);
            border-radius: 4px;
        }

        .category-section::-webkit-scrollbar-thumb:hover,
        .channel-section::-webkit-scrollbar-thumb:hover {
            background-color: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body>
<div class="smarters-layout">
    <!-- Sección de categorías y canales a la izquierda -->
    <div class="category-section">
        <h3>Categorías</h3>
        <div class="category-list">
            <?php
            // Obtener las categorías de canales usando CURL
            $channel_api = getXtreamData('get_live_categories');

            if (!empty($channel_api)) {
                foreach ($channel_api as $category) {
                    $category_id = htmlspecialchars($category['category_id'], ENT_QUOTES, 'UTF-8');
                    $category_name = htmlspecialchars($category['category_name'], ENT_QUOTES, 'UTF-8');
                    echo "<div class='category-item' onclick='loadChannels({$category_id})'>{$category_name}</div>";
                }
            } else {
                echo "<p>No se encontraron categorías.</p>";
            }
            ?>
        </div>
    </div>

    <div class="channel-section">
        <h3>Canales</h3>
        <div id="channel-list" class="channel-list">
            <?php
            if ($selectedCategoryId !== null) {
                $channels = getXtreamData('get_live_streams', ['category_id' => $selectedCategoryId]);

                if (!empty($channels)) {
                    foreach ($channels as $channel) {
                        $stream_id = htmlspecialchars($channel["stream_id"], ENT_QUOTES, 'UTF-8');
                        $title = htmlspecialchars($channel["name"], ENT_QUOTES, 'UTF-8');
                        $desc_image = htmlspecialchars($channel["stream_icon"], ENT_QUOTES, 'UTF-8');

                        echo "<div class='channel-item' onclick='playChannel({$stream_id})'>
                                <img src='{$desc_image}' onerror=\"this.onerror=null;this.src='assets/img/logo.png';\" alt='{$title}'/>
                                <p>{$title}</p>
                              </div>";
                    }
                } else {
                    echo "<p>No se encontraron canales en esta categoría.</p>";
                }
            } else {
                echo '<!-- Los canales se cargarán aquí dinámicamente al hacer clic en una categoría -->';
            }
            ?>
        </div>
    </div>

    <!-- Columna para el reproductor de video -->
    <div class="video-player-column">
        <!-- Sección del reproductor de video -->
        <div class="video-player-container">
            <?php if ($slug == "live" && $id !== null): ?>
                <a class="back_button" href="tvguide.php">
                    <i class="fa fa-arrow-left" aria-hidden="true"></i>
                </a>

                <div class="video-info">
                    <h2><?php echo $content_name; ?></h2> <!-- Nombre del canal -->
                </div>

                <img src="<?php echo htmlspecialchars($logo_url, ENT_QUOTES, 'UTF-8'); ?>" class="video-player-logo" alt="Logo">

                <video id="videojs-player" class="video-js vjs-default-skin" controls preload="auto" autoplay >
                    <source src="<?php echo htmlspecialchars($video_url, ENT_QUOTES, 'UTF-8'); ?>" type="<?php echo htmlspecialchars($mime_type, ENT_QUOTES, 'UTF-8'); ?>">
                </video>
            <?php else: ?>
                <div class="video-player-placeholder">
                    <p>Selecciona un canal para comenzar a ver.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Sección de EPG -->
<div class="epg-section">
    <h3>EPG del Canal</h3>
    <?php
    // Obtener EPG para el canal actual
    $epg_data = getXtreamData('get_short_epg', ['stream_id' => $id]);
    
    if (!empty($epg_data['epg_listings'])):
        echo "<div class='epg-list'>";
        $programs = $epg_data['epg_listings'];
        
        // Obtener la zona horaria del usuario desde la cookie
        $user_timezone = isset($_COOKIE['user_timezone']) ? $_COOKIE['user_timezone'] : 'UTC';
        $userTimezone = new DateTimeZone($user_timezone);
        $utcTimezone = new DateTimeZone('UTC');
        
        // Ordenar programas por hora de inicio
        usort($programs, function($a, $b) {
            $start_a = strtotime($a['start']);
            $start_b = strtotime($b['start']);
            return $start_a - $start_b;
        });
        
        foreach ($programs as $index => $program) {
            // Decodificar título de base64
            $title = htmlspecialchars(base64_decode($program['title']), ENT_QUOTES, 'UTF-8');
            
            // Crear objetos DateTime para inicio y fin en UTC
            $start_datetime = new DateTime($program['start'], $utcTimezone);
            $end_datetime = new DateTime($program['stop'], $utcTimezone);
            
            // Convertir a la zona horaria del usuario
            $start_datetime->setTimezone($userTimezone);
            $end_datetime->setTimezone($userTimezone);
            
            // Formatear las horas en formato de 24 horas en la zona horaria del usuario
            $formatted_start = $start_datetime->format('H:i');
            $formatted_end = $end_datetime->format('H:i');
            
            // Determinar si el programa está actualmente al aire
            $current_time = new DateTime('now', $userTimezone);
            $is_current = ($start_datetime <= $current_time && $end_datetime >= $current_time);
            
            // Agregar clase CSS para programa actual
            $current_class = $is_current ? 'current-program' : '';
            
            // Decodificar descripción si existe
            $description = isset($program['description']) ? 
                          base64_decode($program['description']) : 
                          'No hay descripción disponible';
            
            echo "<div class='epg-item {$current_class}'>
                    <div class='epg-time'>{$formatted_start} - {$formatted_end}</div>
                    <div class='epg-content'>
                        <div class='epg-title'>{$title}</div>
                        <div class='epg-description'>{$description}</div>
                    </div>
                  </div>";
        }
        
        // Mostrar la zona horaria actual (opcional)
        echo "<div class='timezone-info'>Horarios mostrados en: " . $user_timezone . "</div>";
        
        echo "</div>";
        
        // Estilos CSS mejorados
        echo "<style>
            .epg-section {
                background: #2a2a2a;
                border-radius: 10px;
                padding: 15px;
                margin-top: 20px;
            }
            
            .epg-list {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            
            .epg-item {
                display: flex;
                padding: 15px;
                background: #333;
                border-radius: 5px;
                transition: background-color 0.3s ease;
            }
            
            .epg-item:hover {
                background: #444;
            }
            
            .epg-time {
                min-width: 120px;
                font-weight: bold;
                color: #3498db;
                padding-right: 15px;
            }
            
            .epg-content {
                flex-grow: 1;
            }
            
            .epg-title {
                font-weight: bold;
                margin-bottom: 5px;
            }
            
            .epg-description {
                font-size: 0.9em;
                color: #aaa;
                display: none;
            }
            
            .epg-item:hover .epg-description {
                display: block;
            }
            
            .current-program {
                background: #2c3e50;
                border-left: 4px solid #3498db;
            }
            
            .current-program .epg-title {
                color: #3498db;
            }
            
            .timezone-info {
                margin-top: 15px;
                font-size: 0.8em;
                color: #666;
                text-align: right;
            }
        </style>";
    else:
        echo "<p class='no-epg'>No se encontró información de EPG para el canal seleccionado.</p>";
    endif;
    ?>
        </div>
    </div>
</div>

<script>
    var selectedCategoryId = <?php echo json_encode($selectedCategoryId); ?> || null;

    function loadChannels(categoryId) {
        selectedCategoryId = categoryId;
        var channelList = document.getElementById('channel-list');
        channelList.innerHTML = '<p>Cargando canales...</p>'; // Mostrar un mensaje de carga

        var xhr = new XMLHttpRequest();
        xhr.open('GET', '?category_id=' + encodeURIComponent(categoryId), true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState == 4 && xhr.status == 200) {
                channelList.innerHTML = xhr.responseText; // Reemplazar el contenido con los nuevos canales
            }
        };
        xhr.send();
    }

    function playChannel(streamId) {
        window.location = 'tvguide.php?id=' + encodeURIComponent(streamId) + '&slug=live&category_id=' + encodeURIComponent(selectedCategoryId);
    }

    // Configuración del reproductor de video
    <?php if ($slug == "live" && $id !== null): ?>
    var player = videojs('videojs-player', {
        controls: true,
        autoplay: true,
        preload: 'auto',
        fluid: true
    });
    <?php endif; ?>
</script>

</body>
</html>
