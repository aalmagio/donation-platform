<?php
//202504270950
/*
 * Added togglePasswordVisibility for settings.php page
 */

if ( !isset( $_SESSION ) ) {
    session_start();
}
require '../inc/config.inc.php';
require '../inc/data.inc.php';
require 'inc/auth-logout.php';
if ( !function_exists( "GetSQLValueString" ) ) {
    function GetSQLValueString( $theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "" ) {
        if ( PHP_VERSION < 6 ) {
            $theValue = get_magic_quotes_gpc() ? stripslashes( $theValue ) : $theValue;
        }
        //$theValue = function_exists("mysql_real_escape_string") ? mysql_real_escape_string($theValue) : mysql_escape_string($theValue);
        $conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME )or trigger_error( mysqli_error(), E_USER_ERROR );
        $theValue = $conn->real_escape_string( $theValue );
        if ( isset( $conn ) ) {
            mysqli_close( $conn );
        }
        switch ( $theType ) {
            case "text":
                $theValue = ( $theValue != "" ) ? "'" . $theValue . "'": "NULL";
                break;
            case "long":
            case "int":
                $theValue = ( $theValue != "" ) ? intval( $theValue ) : "NULL";
                break;
            case "double":
                $theValue = ( $theValue != "" ) ? doubleval( $theValue ) : "NULL";
                break;
            case "date":
                $theValue = ( $theValue != "" ) ? "'" . $theValue . "'": "NULL";
                break;
            case "defined":
                $theValue = ( $theValue != "" ) ? $theDefinedValue : $theNotDefinedValue;
                break;
        }

        return $theValue;
    }
}
// Gestione caratteri spciali -INIO
function utf8ize( $d ) {
    if ( is_array( $d ) ) {
        foreach ( $d as $k => $v ) {
            $d[ $k ] = utf8ize( $v );
        }
    } else if ( is_string( $d ) ) {
        return utf8_encode( $d );
    }
    return $d;
}
// Gestione caratteri spciali -FINE

$conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME )or trigger_error( mysqli_error(), E_USER_ERROR );
if(!isset($_POST['form']) || $_POST['form'] !='update'){
    $query_settings = "SELECT ENV, PARAMETER, `VALUE`, Descrizione, TYPE, Hint FROM config WHERE Form = '" . LP . "' order by ENV, PARAMETER;";
    $settings = mysqli_query( $conn, $query_settings )or die( mysqli_error( $conn ) );
    $param_settings = mysqli_fetch_assoc( $settings );

    do {
        //define( $param_config['PARAMETER'], $param_config['VALUE'] );
        $form[ $param_settings[ 'ENV' ] ][ $param_settings[ 'PARAMETER' ] ][ 'valore' ] = $param_settings[ 'VALUE' ];
        $form[ $param_settings[ 'ENV' ] ][ $param_settings[ 'PARAMETER' ] ][ 'descrizione' ] = $param_settings[ 'Descrizione' ];
        $form[ $param_settings[ 'ENV' ] ][ $param_settings[ 'PARAMETER' ] ][ 'type' ] = $param_settings[ 'TYPE' ];
        $form[ $param_settings[ 'ENV' ] ][ $param_settings[ 'PARAMETER' ] ][ 'hint' ] = $param_settings[ 'Hint' ];
    } while ( $param_settings = mysqli_fetch_assoc( $settings ) );
}
else{
    $conn = new mysqli(DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    if ($stmt === false) {
        die("Prepare failed: " . htmlspecialchars($conn->error));
    }
    $query_settings = "SELECT PARAMETER, `VALUE` FROM config WHERE Form = ? AND ENV = ? ORDER BY PARAMETER";
    $stmt = $conn->prepare($query_settings);
    // Binding dei parametri
    $form = LP;
    $env = $_POST['ENV'];
    $stmt->bind_param("ss", $form, $env);
    // Esecuzione della query
    $stmt->execute();

    // Binding dei risultati
    $result = $stmt->get_result();

    // Fetch dei dati
    $param_settings = $result->fetch_assoc();

    // Chiusura dello statement e della connessione
    $stmt->close();
    $conn->close();
    if ($param_settings) {
        // Utilizza i dati di $param_settings come necessario
        //Confronto $_POST[] con $param_settings[] e vedo se sono modificati dopo di che efftu l'update
    } else {
        echo "No settings found.";
    }
    /*
    $query_settings = "SELECT ENV, PARAMETER, `VALUE`, Descrizione FROM config WHERE Form = '" . LP . "' AND ENV ='" .$_POST['ENV']."' order by  PARAMETER;";
    $settings = mysqli_query( $conn, $query_settings )or die( mysqli_error( $conn ) );
    $param_settings = mysqli_fetch_assoc( $settings );
    */
}
require( 'inc/head.inc.php' );
?>
<body>
<?php require('inc/nav_hor.inc.php'); ?>

<div class="container-fluid">
    <div class="row">
<?php require('inc/nav_ver.inc.php'); ?>
<main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">

<!--<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">-->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Impostazione di sistema</h1>
    </div>
    <?php if(!isset($_POST['form']) || $_POST['form'] !='update'){ ?>
    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <?php $valtab = 1;
        foreach ( $form as $key => $value ) {
            
            ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?php if($valtab==1){echo 'active';} ?>" id="<?php echo $key; ?>-tab" data-toggle="tab" data-target="#<?php echo $key; ?>" type="button" role="tab"><?php echo $key; ?></button>

            <!--<button class="nav-link <?php if($valtab==1){echo 'active';} ?>" id="<?php echo $key; ?>-tab" data-bs-toggle="tab" data-bs-target="#<?php echo $key; ?>" type="button" role="tab" aria-controls="<?php echo $key; ?>" aria-selected="<?php if($valtab==1){echo 'true';} else{echo 'false';} ?>"><?php echo $key; ?></button>-->
        </li>
        <?php $valtab++;} ?>
    </ul>
    <div class="tab-content" id="myTabContent">
        <?php
        $val = 1;
        foreach ( $form as $key => $value ) {
            ?>
        <div class="tab-pane fade<?php if($val==1){echo " show active";}?>" id="<?php echo $key; ?>" role="tabpanel" aria-labelledby="<?php  echo $key; ?>-tab">
            <div class="container-fluid mt-3"> 
                <!-- Form for <?php echo $key; ?> -->
				 <?php $editFormAction = $_SERVER[ 'PHP_SELF' ];
                if ( isset( $_SERVER[ 'QUERY_STRING' ] ) ) {
                    $editFormAction .= "?" . htmlentities( $_SERVER[ 'QUERY_STRING' ] );
                }?>
                <form action="<?php echo $editFormAction; ?>" name="<?php echo $key; ?>" method="post" class = "form-horizontal" role = "form">
                    <div class="form-group">
                        <?php foreach ( $form [$key] as $k => $v ) { ?>
                        <br>
                        <label for="<?php echo $k ?>" class="control-label col-sm-2"><strong><?php echo $form [$key][$k][ 'descrizione' ] ?></strong>:</label>
                        <div class="col-sm-6">
                            <input type="<?php echo $form [$key][$k][ 'type' ] ?>" id="<?php echo $k ?>" name="<?php echo $k ?>" value="<?php echo $form [$key][$k][ 'valore' ] ?>" size="20" maxlength="265" class="form-control">
                            <?php if ($form [$key][$k][ 'type' ] =="password"){?>
                            <input type="checkbox" onclick="togglePasswordVisibility('<?php echo $k ?>')"> Mostra
                            <?php } ?>
                            <span style="font-size: 0.8em; color: #333333"><?php echo $form [$key][$k][ 'hint' ] ?></span>
                        </div>
                        <?php } ?>
                    </div>
                    <input type="hidden" name="ENV" value="<?php echo $key; ?>">
                    <input type="hidden" name="form" value="update">
                    <input type="submit" value="Aggiorna <?php echo $key; ?>">
                </form>
            </div>
        </div>
        <?php
        $val++;
        }
        ?>
    </div>
    <?php } else { ?>
    Test:
	<?php
    // Recupero ENV dal form e pulizia input
      
	
	$conn = new mysqli(DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
	
$env = isset($_POST['ENV']) ? trim($_POST['ENV']) : '';

if (!empty($env) && defined('LP')) {  // Assicurati che LP sia definita
    // Assegna LP a una variabile perché bind_param() non accetta costanti
    $form_name = LP;

    // Query per ottenere i valori attuali
    $query = "SELECT PARAMETER, `VALUE` FROM config WHERE ENV = ? AND Form = ?";
    $stmt = $conn->prepare($query);

    if ($stmt) {
        $stmt->bind_param("ss", $env, $form_name);
        $stmt->execute();
        $result = $stmt->get_result();

        // Salvo i valori attuali
        $db_values = [];
        while ($row = $result->fetch_assoc()) {
            $db_values[$row['PARAMETER']] = $row['VALUE'];
        }
        $stmt->close();
    } else {
        die("Errore nella query: " . $conn->error);
    }

    // Confronto dati ricevuti con quelli in DB
    $updates = [];
    foreach ($_POST as $parameter => $new_value) {
        if ($parameter !== 'ENV' && $parameter !== 'form') { // Escludi campi nascosti
            $new_value = trim($new_value);
            if (isset($db_values[$parameter]) && $db_values[$parameter] !== $new_value) {
                $updates[$parameter] = $new_value;
            }
        }
    }

    // Se ci sono aggiornamenti, esegui l'UPDATE
    if (!empty($updates)) {
        $update_stmt = $conn->prepare("UPDATE config SET `VALUE` = ? WHERE PARAMETER = ? AND ENV = ? AND Form = ?");
        $form_name = LP;
        foreach ($updates as $param => $val) {
            $update_stmt->bind_param("ssss", $val, $param, $env, $form_name);
            $update_stmt->execute();
        }
        
        $update_stmt->close();
        echo "Aggiornamento completato.";
    } else {
        echo "Nessuna modifica rilevata.";
    }
} else {
    echo "Errore: ENV o LP non sono validi.";
}

// Chiudo la connessione
$conn->close();
?>
    
    

    
    <?php }?>
</main>
</div>
</div>
<?php require('inc/footer.inc.php'); ?>
<?php if (isset($conn)){mysqli_close($conn); }?>