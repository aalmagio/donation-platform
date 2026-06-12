<?php
// 202506 - Removed hardcoded test data
if ( session_status() === PHP_SESSION_NONE ) {
    session_start();
}
require '../inc/config.inc.php';
require '../inc/data.inc.php';
require_once( '../vendor/autoload.php' );

\Stripe\Stripe::setApiKey(SP_SK_APIKEY);

function calculateOrderAmount(array $items): int {
    // Replace this constant with a calculation of the order's amount
    // Calculate the order total on the server to prevent
    // people from directly manipulating the amount on the client
    return 1400;
}

header('Content-Type: application/json');

try {
    // retrieve JSON from POST body
    $jsonStr = file_get_contents('php://input');
    $jsonObj = json_decode($jsonStr);

    if (!$jsonObj || !isset($jsonObj->importo)) {
        http_response_code(400);
        echo json_encode(['error' => 'Dati di pagamento mancanti']);
        exit;
    }

    // Create a PaymentIntent with amount and currency
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => intval($jsonObj->importo),
        'currency' => 'eur',
        'automatic_payment_methods' => [
            'enabled' => true,
        ],
    ]);

    $output = [
        'clientSecret' => $paymentIntent->client_secret,
    ];

    echo json_encode($output);
} catch (\Stripe\Exception\ApiErrorException $e) {
    error_log(date('[Y-m-d H:i:s e] ') . "Stripe API Error: " . $e->getMessage() . PHP_EOL, 3, LOG_FILE);
    http_response_code(500);
    echo json_encode(['error' => 'Errore nel processamento del pagamento']);
} catch (Error $e) {
    error_log(date('[Y-m-d H:i:s e] ') . "Stripe Fatal Error: " . $e->getMessage() . PHP_EOL, 3, LOG_FILE);
    http_response_code(500);
    echo json_encode(['error' => 'Errore interno del server']);
}
