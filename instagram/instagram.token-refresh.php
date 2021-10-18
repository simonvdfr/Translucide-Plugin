<?
//@todo verif de l'origine du script cron


/****************************************************************************************
 * DOCUMENTATION
 * 
 * Ce script est à executer par CRON¹. il permet de récuperer le token enregistré dans 
 * la base de donnée (à l'endroit ou s'affiche les photos instagram) et de rafraichir 
 * celui-ci par curl.
 * Le contenu est alors mis à jour avec le nouveau token et un mail est envoyé pour
 * notifier de la mise à jour du token.
 * 
 * ¹ https://www.infomaniak.com/fr/support/faq/2161/planificateur-de-taches-cronjob
 * 
 *****************************************************************************************/

// Vérifie la configuration de short open tag
if(!ini_get('short_open_tag')) exit('Please put "short_open_tag = On" in php.ini');

@include_once("../../config.php");// Variables
include_once("../../api/function.php");// Fonctions
include_once("../../api/db.php");// Connexion à la db


/****** CONFIGURATION *******/

// coller ci-dessous le token long obtenu initialement lors de l'instalation de l'API
$token = "";


// Email de notification du résultat du CRON¹ 
$email_to = "contact@.net";
$email_from = $GLOBALS['email_contact'];
$subject = "Rafraîchissement token instagram ".$GLOBALS['sitename'];
$header = "Content-type:text/plain; charset=utf-8\r\nFrom:".($email_from ? htmlspecialchars($email_from) : $email_to);

// configuration de la requete
$table = $table_meta;   /* Table du contenu */
$colonne = "val";       /* Colonne du contenu */
$id = 0;                /* ID du contenu */
$type = "footer";       /* Type de contenu */ 



/********** CONTENU **********/

// Construction de la requete select
$sql = " SELECT *, ".$colonne;
$sql.= " FROM ". $table; // table contenant le token
$sql.= " WHERE ".$table.".id = ".$id;
$sql.= " AND ".$table.".type = '".$type."';"; 

// On récupère les données
$sel = $connect->query($sql);
$res = $sel->fetch_assoc();

$fiche = json_decode($res[$colonne], true);


// On controle la présence du token dans le contenu récupéré
if(isset($fiche['token-instagram']) or !empty($fiche['token-instagram']))
{
    // on génere le nouveau token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://graph.instagram.com/refresh_access_token?grant_type=ig_refresh_token&access_token=".$fiche['token-instagram']);
	curl_setopt($ch, CURLOPT_HEADER, 0);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$curl_exec = curl_exec($ch);
    curl_close($ch);

    //echo"<br><br>curl_exec:<br>"; print_r($curl_exec);

    $json_api = json_decode($curl_exec,true);
    //echo"<br><br>json_api:<br>";print_r($json_api);

    $token = $json_api['access_token'];
    $expire = $json_api['expires_in'];

    // on formate le mail
    $message = " ".$subject."\r";
    $message.= "------------------------------------------\r";
    $message.= " Ancien token  : ".$fiche['token-instagram']."\r";
    $message.= " Nouveau token : ".$token."\r";
    $message.= " Ancien token  : ".$expire." secondes";

    mail($email_to,$subject, $message, $header);
}


// on formatage le contenu en json
$fiche['token-instagram'] = $token;
$fiche = addslashes(json_encode($fiche, JSON_UNESCAPED_UNICODE));

// on met à jour l'enregistrement en base de donnée
$sql = " UPDATE ".$table." SET";
$sql.= " ".$colonne." = '".$fiche."'";
$sql.= " WHERE ".$table.".id = ".$id;
$sql.= " AND ".$table.".type = '".$type."';"; 

$connect->query($sql);

?>