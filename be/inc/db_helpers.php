<?php
/**
 * Centralized database helper functions for backend
 * Replaces duplicated GetSQLValueString() and adds safe query execution
 */

if ( !function_exists( "GetSQLValueString" ) ) {
    function GetSQLValueString( $theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "" ) {
        $conn = mysqli_connect( DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME );
        if (!$conn) {
            error_log("GetSQLValueString: DB connection failed");
            return "NULL";
        }
        $theValue = $conn->real_escape_string( $theValue );
        mysqli_close( $conn );

        switch ( $theType ) {
            case "text":
                $theValue = ( $theValue != "" ) ? "'" . $theValue . "'" : "NULL";
                break;
            case "long":
            case "int":
                $theValue = ( $theValue != "" ) ? intval( $theValue ) : "NULL";
                break;
            case "double":
                $theValue = ( $theValue != "" ) ? doubleval( $theValue ) : "NULL";
                break;
            case "date":
                $theValue = ( $theValue != "" ) ? "'" . $theValue . "'" : "NULL";
                break;
            case "defined":
                $theValue = ( $theValue != "" ) ? $theDefinedValue : $theNotDefinedValue;
                break;
        }
        return $theValue;
    }
}

/**
 * Safe query execution - replaces die(mysqli_error()) pattern
 * Logs the real error, shows generic message to user
 */
function safe_query($conn, $query, $context = '') {
    $result = mysqli_query($conn, $query);
    if (!$result) {
        $error = mysqli_error($conn);
        error_log(date('[Y-m-d H:i:s e] ') . "SQL Error in $context: $error | Query: $query" . PHP_EOL, 3, LOG_FILE);
        return false;
    }
    return $result;
}

/**
 * Safe DB connection - replaces trigger_error pattern
 */
function safe_db_connect() {
    $conn = mysqli_connect(DB_IP, DB_USER, DB_PASSWORD, DB_DBNAME);
    if (!$conn) {
        error_log(date('[Y-m-d H:i:s e] ') . "DB connection failed: " . mysqli_connect_error() . PHP_EOL, 3, LOG_FILE);
        return false;
    }
    return $conn;
}
