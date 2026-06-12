<?php
//202504270950
/*
 * Added settings.php page
 */
?>
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="sidebar-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item"> <a class="nav-link<?php if ($_SERVER['PHP_SELF'] =="/oneoff.php") echo ' active'  ?>" href="oneoff.php"> <span data-feather="home"></span> Verifica Donazioni
                <?php if ($_SERVER['PHP_SELF'] =="/oneoff.php"){ ?>
                <span class="sr-only">(current)</span> &lt;
                <?php } ?>
                </a> </li>
            <li class="nav-item"> <a class="nav-link<?php if ($_SERVER['PHP_SELF'] =="/search.php") echo ' active'  ?>" href="search.php"> <span data-feather="file"></span> Cerca Transazioni NON OK
                <?php if ($_SERVER['PHP_SELF'] =="/search.php"){ ?>
                <span class="sr-only">(current)</span> &lt;
                <?php } ?>
                </a> </li>
            <li class="nav-item"><a  class="nav-link" href="export-xls.php" target="_blank"><span data-feather="file">Scarica tutte le donazioni OK </span></a></li>
            <hr>
            <li class="nav-item"> <a class="nav-link<?php if ($_SERVER['PHP_SELF'] =="/partner.php") echo ' active'  ?>" href="partner.php"> <span data-feather="file"></span> Voucher Partner
                <?php if ($_SERVER['PHP_SELF'] =="/partner.php"){ ?>
                <span class="sr-only">(current)</span> &lt;
                <?php } ?>
                </a> </li>
            <li class="nav-item"> <a class="nav-link<?php if ($_SERVER['PHP_SELF'] =="/partner_list.php") echo ' active'  ?>" href="partner_list.php"> <span data-feather="file"></span> Elenco Partner
                <?php if ($_SERVER['PHP_SELF'] =="/partner_list.php"){ ?>
                <span class="sr-only">(current)</span> &lt;
                <?php } ?>
                </a> </li>
            <?php if (isset($_SESSION) && "A" == $_SESSION['MM_UserGroup']) { ?>
            <li class="nav-item"> <a class="nav-link<?php if ($_SERVER['PHP_SELF'] =="/add_partner.php") echo ' active'  ?>" href="add_partner.php"> <span data-feather="file"></span> Aggiungi Partner
                <?php if ($_SERVER['PHP_SELF'] =="/add_partner.php"){ ?>
                <span class="sr-only">(current)</span> &lt;
                <?php } ?>
                </a> </li>
            <?php } ?>
            <li class="nav-item"><a  class="nav-link" href="partner_export-xls.php" target="_blank"><span data-feather="file">Scarica i voucher dei partner </span></a></li>
            <hr>
            
            <!--<li class="nav-item"> <a class="nav-link<?php if ($_SERVER['PHP_SELF'] =="/regularcc.php") echo ' active'  ?>" href="regularcc.php"> <span data-feather="shopping-cart"></span> Verifica Regolari con CdC <?php if ($_SERVER['PHP_SELF'] =="/regularcc.php"){ ?> <span class="sr-only">(current)</span> &lt;<?php } ?></a> </li>
					<li class="nav-item"> <a class="nav-link<?php if ($_SERVER['PHP_SELF'] =="/sdd.php") echo ' active'  ?>" href="sdd.php"> <span data-feather="users"></span> Verifica Mandati SDD <?php if ($_SERVER['PHP_SELF'] =="/sdd.php"){ ?> <span class="sr-only">(current)</span>  &lt;<?php } ?></a> </li>--> 
            <!--<li class="nav-item"> <a class="nav-link<?php if ($_SERVER['PHP_SELF'] =="/tgift.php") echo ' active'  ?>" href="tgift.php"> <span data-feather="bar-chart-2"></span> Tessere in Regalo <?php if ($_SERVER['PHP_SELF'] =="/tgift.php"){ ?> <span class="sr-only">(current)</span> &lt;<?php } ?></a> </li>-->
            <li class="nav-item"> <a class="nav-link<?php if ($_SERVER['PHP_SELF'] =="/cerca.php") echo ' active'  ?>" href="cerca.php"> <span data-feather="bar-chart-2"></span> Cerca Transazioni
                <?php if ($_SERVER['PHP_SELF'] =="/cerca.php"){ ?>
                <span class="sr-only">(current)</span> &lt;
                <?php } ?>
                </a> </li>
               <li class="nav-item"> <a class="nav-link<?php if ($_SERVER['PHP_SELF'] =="/stats.php") echo ' active'  ?>" href="stats.php"> <span data-feather="bar-chart-2"></span> Statistiche
                <?php if ($_SERVER['PHP_SELF'] =="/stats.php"){ ?>
                <span class="sr-only">(current)</span> &lt;
                <?php } ?>
                </a> </li>
            <?php if (isset($_SESSION) && "A" == $_SESSION['MM_UserGroup']) { ?>
            <li class="nav-item"> <a class="nav-link<?php if ($_SERVER['PHP_SELF'] =="/remailer.php") echo ' active'  ?>" href="remailer.php"> <span data-feather="bar-chart-2"></span> Invia promemoria mail
                <?php if ($_SERVER['PHP_SELF'] =="/remailer.php"){ ?>
                <span class="sr-only">(current)</span> &lt;
                <?php } ?>
                </a> </li>
            <li class="nav-item"> <a class="nav-link<?php if ($_SERVER['PHP_SELF'] =="/dati_full.php") echo ' active'  ?>" href="dati_full.php"> <span data-feather="bar-chart-2"></span> Visualizza dati completi
                <?php if ($_SERVER['PHP_SELF'] =="/dati_full.php"){ ?>
                <span class="sr-only">(current)</span> &lt;
                <?php } ?>
                </a> </li>
            <hr>
            <li class="nav-item"> <a class="nav-link<?php if ($_SERVER['PHP_SELF'] =="/users.php") echo ' active'  ?>" href="users.php"> <span data-feather="bar-chart-2"></span> Gestisci gli utenti
                <?php if ($_SERVER['PHP_SELF'] =="/users.php"){ ?>
                <span class="sr-only">(current)</span> &lt;
                <?php } ?>
                </a> </li>
            <li class="nav-item"> <a class="nav-link<?php if ($_SERVER['PHP_SELF'] =="/add_user.php") echo ' active'  ?>" href="add_user.php"> <span data-feather="bar-chart-2"></span> Aggiungi un utente
                <?php if ($_SERVER['PHP_SELF'] =="/add_user.php"){ ?>
                <span class="sr-only">(current)</span> &lt;
                <?php } ?>
                </a> </li>
            <hr>
            <li class="nav-item"> <a class="nav-link<?php if ($_SERVER['PHP_SELF'] =="/mod_campaign.php") echo ' active'  ?>" href="mod_campaign.php"> <span data-feather="bar-chart-2"></span> Modifica campagna
                <?php if ($_SERVER['PHP_SELF'] =="/mod_campaign.php"){ ?>
                <span class="sr-only">(current)</span> &lt;
                <?php } ?>
                </a> </li>
            <li class="nav-item"> <a class="nav-link<?php if ($_SERVER['PHP_SELF'] =="/mod_esito.php") echo ' active'  ?>" href="mod_esito.php"> <span data-feather="bar-chart-2"></span> Modifica esito
                <?php if ($_SERVER['PHP_SELF'] =="/mod_esito.php"){ ?>
                <span class="sr-only">(current)</span> &lt;
                <?php } ?>
                </a> </li>
            <li class="nav-item"> <a class="nav-link<?php if ($_SERVER['PHP_SELF'] =="/mod_data.php") echo ' active'  ?>" href="mod_data.php"> <span data-feather="bar-chart-2"></span> Modifica dati donatore
                <?php if ($_SERVER['PHP_SELF'] =="/mod_data.php"){ ?>
                <span class="sr-only">(current)</span> &lt;
                <?php } ?>
                </a> </li>
            <li class="nav-item"> <a class="nav-link<?php if ($_SERVER['PHP_SELF'] =="/settings.php") echo ' active'  ?>" href="settings.php"> <span data-feather="bar-chart-2"></span> Impostazione del sistema
                <?php if ($_SERVER['PHP_SELF'] =="/settings.php"){ ?>
                <span class="sr-only">(current)</span> &lt;
                <?php } ?>
                </a> </li>
            <?php } ?>
        </ul>
    </div>
</nav>
