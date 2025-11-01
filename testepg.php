<?php
// Credenciales para la API
$dns = "http://ares-tv.com:80";
$username = "betotun2024";
$password = "EyQO4k";

// ID del canal "Azteca 7"
$channel_id = 109777; // ID de "Azteca 7"

// Función para obtener datos desde Xtream Codes API usando CURL
function getXtreamData($dns, $username, $password, $endpoint, $params = []) {
    $url = $dns . "/player_api.php?username=" . urlencode($username) . "&password=" . urlencode($password) . "&action=" . urlencode($endpoint);
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

// Obtener la EPG para el canal específico
$epg_info = getXtreamData($dns, $username, $password, 'get_short_epg', ['stream_id' => $channel_id]);

// Mensajes de depuración para la respuesta de la API
echo "<pre>";
print_r($epg_info);
echo "</pre>";

if (!empty($epg_info['epg_listings'])) {
    $epg_data = $epg_info['epg_listings'];
    $selected_channel_name = "Azteca 7";
} else {
    echo "No se encontró información de EPG para el canal Azteca 7.";
}

// Función para decodificar Base64
function decodeBase64IfNeeded($data) {
    if (base64_encode(base64_decode($data, true)) === $data) {
        return base64_decode($data);
    }
    return $data;
}

// Función para convertir y formatear el tiempo de la EPG
function formatEPGTime($timestamp) {
    if (is_numeric($timestamp)) {
        // Si el timestamp está en milisegundos, conviértelo a segundos
        if ($timestamp > 10000000000) { 
            $timestamp = intval($timestamp / 1000);
        }
        // Devuelve el tiempo en formato 'H:i' (hora:minuto en 24 horas)
        return date('H:i', $timestamp);  
    } else {
        // Si el timestamp es una cadena de fecha, conviértelo
        $time = strtotime($timestamp);
        return ($time !== false) ? date('H:i', $time) : '00:00';
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obtener EPG - Azteca 7</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #1a1a1a;
            color: #f1f1f1;
            padding: 20px;
        }
        .epg-section {
            margin-top: 20px;
            background-color: #2c2c2c;
            padding: 15px;
            border-radius: 5px;
        }
    </style>
</head>
<body>

<h1>EPG de Azteca 7</h1>

<?php if (isset($epg_data) && !empty($epg_data)): ?>
    <div class="epg-section">
        <h3>EPG para: <?php echo htmlspecialchars($selected_channel_name); ?></h3>
        <ul>
            <?php foreach ($epg_data as $program): ?>
                <li>
                    <strong><?php echo htmlspecialchars(decodeBase64IfNeeded($program['title']), ENT_QUOTES, 'UTF-8'); ?></strong><br>
                    <?php echo formatEPGTime($program['start']); ?> a <?php echo formatEPGTime($program['end']); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php else: ?>
    <p>No se encontraron datos de EPG.</p>
<?php endif; ?>

</body>
</html>
