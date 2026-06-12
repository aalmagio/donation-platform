<?php
//202504270950
/*
 * Added togglePasswordVisibility for settings.php page
 */
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="description" content="">
<meta name="author" content="Donation Platform">
<meta name="generator" content="Jekyll v4.1.1">
<?php
$page_title = $_SERVER[ 'PHP_SELF' ];
$page_title = ucfirst( substr( $page_title, 1, -4 ) )
?>
<title>BE Donazioni Dashboard -
<?= $page_title;?>
</title>
<link rel="canonical" href="retrive.php">

<!-- Bootstrap core CSS -->
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">

<!--<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script> --> 
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script> 
<script>window.jQuery || document.write('<script src="/docs/4.5/assets/js/vendor/jquery.slim.min.js"><\/script>')</script> 
<!--<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous">
</script>--> 
<script src='https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js' type='text/javascript'></script> 

<!-- Favicons -->
<link rel="apple-touch-icon" sizes="57x57" href="img/apple-icon-57x57.png">
<link rel="apple-touch-icon" sizes="60x60" href="img/apple-icon-60x60.png">
<link rel="apple-touch-icon" sizes="72x72" href="img/apple-icon-72x72.png">
<link rel="apple-touch-icon" sizes="76x76" href="img/apple-icon-76x76.png">
<link rel="apple-touch-icon" sizes="114x114" href="img/apple-icon-114x114.png">
<link rel="apple-touch-icon" sizes="120x120" href="img/apple-icon-120x120.png">
<link rel="apple-touch-icon" sizes="144x144" href="img/apple-icon-144x144.png">
<link rel="apple-touch-icon" sizes="152x152" href="img/apple-icon-152x152.png">
<link rel="apple-touch-icon" sizes="180x180" href="img/apple-icon-180x180.png">
<link rel="icon" type="image/png" sizes="192x192"  href="img/android-icon-192x192.png">
<link rel="icon" type="image/png" sizes="32x32" href="img/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="96x96" href="img/favicon-96x96.png">
<link rel="icon" type="image/png" sizes="16x16" href="img/favicon-16x16.png">
<meta name="msapplication-TileImage" content="img/ms-icon-144x144.png">
<meta name="theme-color" content="#631719">
<style>
.bd-placeholder-img {
    font-size: 1.125rem;
    text-anchor: middle;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}

@media (min-width: 768px) {
.bd-placeholder-img-lg {
    font-size: 3.5rem;
}
}
</style>
<!-- Custom styles for this template --> 
<script language="javascript" src="calendar/calendar.js"></script> 
<script language="javascript">
function togglePasswordVisibility(id) {
  var x = document.getElementById(id);
  if (x.type === "password") {
    x.type = "text";
  } else {
    x.type = "password";
  }
}    
</script>
<link href="calendar/calendar.css" rel="stylesheet" type="text/css"/>
<?php require_once('calendar/classes/tc_calendar.php'); ?>
<link href="css/dashboard.css" rel="stylesheet">
</head>

