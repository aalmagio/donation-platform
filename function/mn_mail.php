<?php
require '../inc/config.inc.php';
require '../inc/data.inc.php';
function CleanMyJSON( $json ) {
    return ( preg_replace( '/("[a-zA-Z0-9_\-]+"\s*+:\s*(null|"\s*"|NULL),\s*)*/', '', $json ) );
}

// Integrazione MagNews (https://www.magnews.it/)
// Configurazione OAuth nell'account MagNews:
//   OAuth redirect URI: {URL_DI_BASE}/function/mn_auth_callback.php
//   Auth Dialog URL: https://be-mn1.mag-news.it/be/oauth/dialog?response_type=code&account={ACCOUNT}&client_id={APP_ID}&scope=full&redirect_uri={REDIRECT_URI}&state=STATE
//   Token location URL: https://be-mn1.mag-news.it/be/oauth/token
// Le credenziali (MN_APP_SECRET, MN_REFRESH_TOKEN) vanno nella tabella config / .env, mai nel codice.


function Upsert_Magnews( $mn_contact ) {
    //Scrivo in MagNews - Inizio
    $url_ch = MN_API_URL . "/v19/contacts/subscribe";

    if(!isset($mn_contact['id_donatore']) || $mn_contact['id_donatore']==""){
        $values = array(
            "email" => $mn_contact['email'],
            "name" => $mn_contact['nome'],
            "surname" => $mn_contact['cognome'],
            "cell" => $mn_contact['tel'],
        ); 
    } else {
        $values = array(
            "email" => $mn_contact['email'],
            "id_donatore" => $mn_contact['id_donatore']
        );
    }
    if (!isset ($mn_contact['mn_template'])) $mn_contact['mn_template'] = 1;
    if (!isset ($mn_contact['mn_db'])) $mn_contact['mn_db'] = 1;
    if (!isset ($mn_contact['sendmail'])) $mn_contact['sendmail'] = false;
    if (!isset ($mn_contact['enterworkflow'])) $mn_contact['enterworkflow'] = false;
    if (!isset ($mn_contact['idworkflow'])) $mn_contact['idworkflow'] = 1;
  
    $options =array(
        "idtemplate" => $mn_contact['mn_template'],
        "iddatabase" => $mn_contact['mn_db'],
        "sendemail" => $mn_contact['sendmail'],
        "enterworkflow" => $mn_contact['enterworkflow'], 
        "idworkflow" => $mn_contact['idworkflow']
    );
        
    $data = array(
        "options" => $options, 
        "values" => $values
    );

    $data_string = json_encode($data, JSON_UNESCAPED_UNICODE);
    $data_string = CleanMyJSON( $data_string );
    $access_token = MN_APP_SECRET; //see OAuth 2 section.
    if ( DEBUG == true ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "Chiamata wsc_save_donor Mentor: " . $data_string . PHP_EOL, 3, LOG_FILE );
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,  $url_ch );
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $headers = array("Content-Type: application/json", "Authorization: Bearer $access_token");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    //$result = curl_exec($ch);
    $result = json_decode( curl_exec( $ch ), true );
    if ( DEBUG == true ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "Esito wsc_save_donor Mentor: " . json_encode( $result ) . PHP_EOL, 3, LOG_FILE );
    }
    curl_close( $ch );

    if ( $result[ 'ok' ] == "true" ) {
        return  ($result);
    } else {
        return (  $result['errors']['0']['type']);

    }

}

function AddDonation_Magnews( $mn_donation ) {
    //Scrivo in MagNews - Inizio
    $url_ch = MN_API_URL . "/v19/entities/donazioni/data";
    
    $fields = array(
        "id_donatore" => $mn_donation['id_donatore'],
        "codice_donazione" => $mn_donation['codice_donazione'],
        "importo" => $mn_donation['importo'],
        "data_donazione" => $mn_donation['data_donazione'],
        "campagna" => $mn_donation['campagna'],
        "modalita_pagamento" => $mn_donation['modalita_pagamento'],
        "piattaforma" => $mn_donation['piattaforma']     
    );
    $options =array(
        "operationtype" => "upsert"
    );
    
    $data = array(
        "options" => $options, 
        "fields" => $fields
    );

    $data_string = json_encode($data, JSON_UNESCAPED_UNICODE);
    $data_string = CleanMyJSON( $data_string );
    $access_token = MN_APP_SECRET; //see OAuth 2 section.
    if ( DEBUG == true ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "Chiamata wsc_save_donor Mentor: " . $data_string . PHP_EOL, 3, LOG_FILE );
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,  $url_ch );
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $headers = array("Content-Type: application/json", "Authorization: Bearer $access_token");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    //$result = curl_exec($ch);
    $result = json_decode( curl_exec( $ch ), true );
    if ( DEBUG == true ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "Esito wsc_save_donor Mentor: " . json_encode( $result ) . PHP_EOL, 3, LOG_FILE );
    }
    curl_close( $ch );
    
    if ( $result[ 'ok' ] == "true" ) {
        return  ($result);
    } else {
        return (  $result['errors']['0']['type']);
    }
}
/*
$dataset = array(
        "email" => "test@example.org",
        "nome" => "Alberto",
        "cognome" => "Rossi",
        "tel" => "3472775038",
        "db"=> 1,
);

$mn_operation = call_user_func_array( 'Upsert_Magnews', array( $dataset ) ); // Scrivo il contatto in magnews
echo "<br>Inserisco<br>";
print_r($mn_operation);

if(is_array($mn_operation)){
    $dataset +=[
        "id_donatore" => $mn_operation['idcontact'], // WR + Id_a
        "codice_donazione" => "D-202405125162589423-DD", //CodTrans
        "importo" => "50", //Importo
        //"data_donazione" => "15/05/2024 10:05", //data
        "campagna" => "SA.GEN.WEB",
        "modalita_pagamento" => "PP", //pay_method
        "piattaforma" =>"PdB" 
    ];    
    $mn_update = call_user_func_array( 'Upsert_Magnews', array($dataset)); // Scrivo il contatto in magnews
    echo "<br>Aggiorno<br>";
    print_r($mn_operation);
    
    $mn_donation = call_user_func_array( 'AddDonation_Magnews', array( $dataset ) ); // Scrivo il contatto in magnews
    echo "<br>Donazione<br>";
    print_r($mn_donation);
}

echo "<hr>";
print_r($dataset);

*/
