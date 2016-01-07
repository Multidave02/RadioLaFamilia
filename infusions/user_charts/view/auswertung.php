<?php
/**
 * Created by PhpStorm.
 * User: preacher
 * Date: 15.10.15
 * Time: 00:53
 */

require_once "../../../maincore.php";
include INFUSIONS."user_charts/infusion_db.php";

require_once INFUSIONS."user_charts/lib/SearchCover.php";
require_once INFUSIONS."user_charts/lib/StatusMessage.php";
session_start();

$resultNew = dbquery("SELECT * FROM ".DB_NEUEINTRAG);
$resultRaus = dbquery("SELECT * FROM ".DB_CHARTS." WHERE chart_woche = 8 ");
$rowsRaus = dbrows($resultRaus);
$rowsNew = dbrows($resultNew);
$sonntag = 7;
$nochTage = $sonntag - date('N');


echo '<link rel="stylesheet" href="' . INFUSIONS . 'user_charts/css/my.css">';
echo "<link rel='stylesheet' href='". INFUSIONS."user_charts/css/font-awesome-4.4.0/css/font-awesome.min.css'>";
echo "<div style='height: 100%; color: black;'>";
echo "<h4 style='text-align: center;margin: 10px 0 40px 0;'> Wochen auswertung </h4>";
date_default_timezone_set('Europe/Berlin');
$result = json_decode($_SESSION['erg']);
/* Wann Auswertung Aktiv ist */
if (date('w') == 3 && date('H') >= 19){
if($rowsNew >= $rowsRaus) {
        if(!$result){
            echo "<form action=" .$aidlink . " method='post'>";
            echo "<input id='ausw' type='submit' name='auswertung' value='Auswerten JETZT !' style='width: 99%; height: 50px;border-color: darkred;'>";
            echo "</form>";
        }else{
            echo "<table align='center' class='GeneratedTable'>";
            for ($i = 0; $i < count($result); $i++) {
                if($result[$i][0]){
                    echo "<tr  align='center'>";
                    echo "<td>".$result[$i][1]."</td>";
                    echo "<td><span class='fa fa-check fa-3x'></span></td>";
                    echo "</tr>";
                }else{
                    echo "<tr  align='center'>";
                    echo "<td>".$result[$i][1]."</td>";
                    echo "<td><span class='fa fa-ban fa-3x'></span></td>";
                    echo "</tr>";
                }
            }
            echo "</table>";
    }
    }else{
        echo "<h4 class='text'>Bitte erst neue Songs einpflegen ! </h4>";
    }
}else{
    echo "<h4 class='text'>Noch " . $nochTage . " Tage bis Sonntag 17 Uhr</h4>";
}

echo "</div>";

// TODO-Michy wenn auswertung fertig für den Rest Sonntag ein sperre bauen