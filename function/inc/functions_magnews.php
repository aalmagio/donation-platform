<?php

function Upsert_Magnews( $mn_contact ) {
    //Scrivo in MagNews - Inizio
    $url_ch = MN_API_URL . "/v19/contacts/subscribe";

    if(!isset($mn_contact['id_donatore']) || $mn_contact['id_donatore']==""){
        if(!isset ( $mn_contact['fonte']))  {$mn_contact['fonte'] = ID_CAMPAGNA_DEFAULT;}
        $values = array(
            "email" => $mn_contact['email'],
            "name" => mb_ucfirst($mn_contact['nome']), //Modifico 
            "surname" => mb_ucfirst($mn_contact['cognome']), //Modifico
            "cell" => $mn_contact['tel'],
            "fonte" => $mn_contact['fonte'],
            //"Referral Code" => $mn_contact['referral'],
            "PERSONALCODE" => $mn_contact['CodicePersonale'],
        ); 
    } else {
        $values = array(
            "email" => $mn_contact['email'],
            "cod_donatore" => $mn_contact['id_donatore']
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
        error_log( date( '[Y-m-d H:i:s e] ' ) . "Chiamata Upsert Magnews: " . $data_string . PHP_EOL, 3, LOG_FILE );
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
        error_log( date( '[Y-m-d H:i:s e] ' ) . "Esito Upsert Magnews: " . json_encode( $result ) . PHP_EOL, 3, LOG_FILE );
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
    $secret = md5(  $mn_donation['Id_a'] . SALT_MAIL );
    
    $fields = array(
        "id_donatore" => $mn_donation['id_donatore'],
        "codice_donazione" => $mn_donation['codice_donazione'],
        "importo" => $mn_donation['importo'],
        //"data_donazione" => $mn_donation['data_donazione'],
        "campagna" => $mn_donation['campagna'],
        "modalita_pagamento" => $mn_donation['modalita_pagamento'],
        "piattaforma" => $mn_donation['piattaforma'],
        "param_d" => $mn_donation['Id_a'],
        "param_s" => $secret
    );
    $options =array(
        "operationtype" => "insert"
    );
    
    $data = array(
        "options" => $options, 
        "fields" => $fields
    );

    $data_string = json_encode($data, JSON_UNESCAPED_UNICODE);
    $data_string = CleanMyJSON( $data_string );
    $access_token = MN_APP_SECRET; //see OAuth 2 section.
    if ( DEBUG == true ) {
        error_log( date( '[Y-m-d H:i:s e] ' ) . "Chiamata AddDonation Magnews: " . $data_string . PHP_EOL, 3, LOG_FILE );
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
        error_log( date( '[Y-m-d H:i:s e] ' ) . "Esito AddDonation Magnews: " . json_encode( $result ) . PHP_EOL, 3, LOG_FILE );
    }
    curl_close( $ch );
    
    //if ( $result[ 'ok' ] == "true" ) {
        return  ($result);
    //} else {
    //    return (  $result['errors']['0']['type']);
    //}
}

