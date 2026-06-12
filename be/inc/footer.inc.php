<footer role="footer" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
<?php 
if(USE_SANDBOX == true) {
    echo "<hr><div class=\"debug\">";
	echo "<strong>Debug Variabili</strong> : <br>";
	echo "<strong>DB Name</strong>: " .DB_DBNAME ."<br>";
    echo "<strong>POS Name</strong>: " .GP_COD_ESE ."<br>";
    if (isset($query_donazione)){ echo "<strong>Query donazioni</strong>: ". $query_donazione;}
    if (isset($_SESSION))  {
        echo "<strong>SESSION Variable(s)</strong>: <br>"; 
        foreach ($_SESSION as $key => $value){echo "<em>". $key ."</em> = ". $value ."<br>";}
    }
	if (isset($_POST)){
        echo "<strong>POST Variable(s)</strong>: <br>";
        foreach ($_POST as $key => $value){echo  "<em>".$key ."</em> = ". $value ."<br>";}
    }
	if (isset($_GET)) {
        echo "<strong>GET Variable(s)</strong>: <br>";
        foreach ($_GET as $key => $value){echo  "<em>".$key ."</em> = ". $value ."<br>";}
    }
    echo "<br>--- Variabili ambiente ---". "<br>";
    echo "url_di_base: " . $url_di_base . "<br>";
    echo $_SERVER['PHP_SELF'];
    echo "<br>--- Costanti ambiente ---". "<br>";
    if (USE_GESTPAY == true){ echo "GestPay: " . GP_URLAPI . "<br>";}
    if (USE_PAYPAL == true){echo "PayPal: " . PP_URLAPI . "<br>";}
    if (USE_MENTOR == true){echo "Mentor:  " . MENTOR_API_URL . "<br>";}
    echo "DON_WS: " . DON_WS . "<br>";
    echo "DB: " . DB_DBNAME. "<br>";
    echo "</div>";
}
?>
<!-- DeBug -->
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.9.0/feather.min.js"></script> 
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.3/Chart.min.js"></script> 

</body>
</html>
