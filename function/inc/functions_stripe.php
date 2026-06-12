<?php // Stripe
function ChargeOrderStripe( $dati ) {
    \Stripe\Stripe::setApiKey( SP_SK_APIKEY );
    $card = array(
        'number' => $dati->cartan,
        'name' => $dati->titolare,
        'exp_month' => $dati->exp_mm,
        'exp_year' => $dati->exp_yy,
        'cvv' => $dati->cvv,
        'receipt_email' => $dati->mail

    );
    $metadata = array(
        'cotTrans' => $dati->CodTrans,
        'nome' => $dati->titolare,
        'email' => $dati->mail

    );
    $success = null;
    try {
        $c = \Stripe\Charge::create(
            array(
                'amount' => $dati->importo,
                'currency' => 'eur',
                'description' => $dati->causale,
                'card' => $card,
                'metadata' => $metadata
            )
        );
        $success = 1;
    } catch ( Stripe_CardError $e ) {
        $error = $e->getMessage();
    } catch ( Stripe_InvalidRequestError $e ) {
        // Invalid parameters were supplied to Stripe's API
        $error = $e->getMessage();
    } catch ( Stripe_AuthenticationError $e ) {
        // Authentication with Stripe's API failed
        $error = $e->getMessage();
    } catch ( Stripe_ApiConnectionError $e ) {
        // Network communication with Stripe failed
        $error = $e->getMessage();
    } catch ( Stripe_Error $e ) {
        // Display a very generic error to the user, and maybe send
        // yourself an email
        $error = $e->getMessage();
    } catch ( Exception $e ) {
        // Something else happened, completely unrelated to Stripe
        $error = $e->getMessage();
    }
    if ( 1 == $success ) { // Payment 

    } else { // Error
    }

}