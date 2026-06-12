<?php
/* Simple Log Rotate
 * 20211228
 * Initial release
 */

//CONF
require dirname(__DIR__) . '/inc/config.inc.php';
$log_folder = __DIR__ . '/log/';
$host_name = parse_url(URL_DI_BASE, PHP_URL_HOST) . '/be';
$to = ORG_EMAIL;
// CONF End

//Check if archive dir exists and create it if not
if ( !file_exists( $log_folder . "archive" ) ) {
    mkdir( $log_folder . "archive", 0755, true );
}
$archive_folder = $log_folder . "archive/";
// Mail Data
$object = "Log rotation for " . $host_name;
$bodymail = "Log rotation in folder " . $log_folder. "\n";

$files = glob( $log_folder . "*.log" );
foreach ( $files as $k => $v ) {
    //echo $k . " = " . $v . "<br>";
    $archive_name = substr( $v, strlen( $log_folder ), -4 );
    $archive_name .= date( 'YmdHi' );
    try {
        $a = new PharData( $archive_folder . $archive_name . '.tar' );
        // ADD FILES TO archive.tar FILE
        $a->addFile( $v );
        //$a->addFile( 'index.php' );

        // COMPRESS archive.tar FILE. COMPRESSED FILE WILL BE archive.tar.gz
        $a->compress( Phar::GZ );

        // NOTE THAT BOTH FILES WILL EXISTS. SO IF YOU WANT YOU CAN UNLINK archive.tar
        unlink( $archive_folder . $archive_name . '.tar' );
        $bodymail .= "File " . $v . " was archived as  " . $archive_folder . $archive_name . ".tar.gz\n";
    } catch ( Exception $e ) {
        // echo "Exception : " . $e;
        $bodymail .= "Exception : " . $e . "\n";
    }
    $f = @fopen( $v, "r+" );
    if ( $f !== false ) {
        ftruncate( $f, 0 );
        fclose( $f );
    }
}
mail( $to, $object, $bodymail );