<?php
// Configuración de la API
$url = 'https://gire.com';

// Datos de la transacción en un array estructurado
$datos = [
    "total" => 100.2,
    "currency" => "ARS",
    "reference" => "REF-" . bin2hex(random_bytes(5)), // Referencia única segura
    "description" => "Descripción de la Venta",
    "items" => [
        [
            "quantity" => 2,
            "description" => "Mi Producto",
            "total" => 50
        ],
        [
            "quantity" => 1,
            "description" => "Mi otro producto",
            "total" => 50.2
        ]
    ],
    "options" => [
        "domain" => "colmed1.com.ar",
        "embed" => true,
        "embedVersion" => "1.2.0"    
    ],
    "return_url" => "https://www.colmed1.com.ar",
    "webhook" => "https://www.colmed1.com.ar"
];

// Encabezados requeridos por Gire
$headers = [
    'Content-Type: application/json',
    'x-lang: es',
    'x-access-token: d31f0721-2f85-44e7-bcc6-15e19d1a53cc',
    'x-api-key: zJ8LFTBX6Ba8D611e9io13fDZAwj0QmKO1Hn1yIj',
    'cache-control: no-cache'
];

// Inicialización de cURL (Nativo en PHP 8.2)
$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS     => json_encode($datos),
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_SSL_VERIFYPEER => true, // Seguridad obligatoria para pagos
    CURLOPT_TIMEOUT        => 30    // Tiempo límite de espera
]);

$response = curl_exec($ch);
var_dump($response);
    echo '<br>';
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    $error_msg = curl_error($ch);
    curl_close($ch);
    die("Error de conexión: " . $error_msg);
}

curl_close($ch);

// Procesar respuesta
if ($http_code === 200 || $http_code === 201) {
    $resultado = json_decode($response, true);
    var_dump($resultado);
    echo '<br>';
    // Redirigir al usuario a la URL de pago recibida
    if (isset($resultado['url'])) {
        header("Location: " . $resultado['url']);
        exit;
    } else {
        echo "Respuesta inesperada: " . $response;
    }
} else {
    echo "Error de la API (Código $http_code): " . $response;
}
