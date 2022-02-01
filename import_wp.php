<?php
//--------------------------------------------
// CONFIGURATION
//--------------------------------------------
include_once("api/function.php");// Fonctions
include_once("config.php");// Fonctions
    
// Variables de la base de données
$GLOBALS['wp_prefix'] = "wp_";
$GLOBALS['wp_charset'] = 'utf8';

$GLOBALS['table_post'] = $GLOBALS['tp'] = $GLOBALS['wp_prefix'].'posts';

if($dev) {// Dev local
	$GLOBALS['wp_server'] = "localhost";
	$GLOBALS['wp_user'] = "root";
	$GLOBALS['db'] = "";
	$GLOBALS['wp_pwd'] = "";
}
else {
	$GLOBALS['wp_server'] = "";
	$GLOBALS['wp_user'] = "";
	$GLOBALS['db'] = "";
	$GLOBALS['wp_pwd'] = "";
}

// Connexion a la base de données
if(isset($GLOBALS['wp_server']) and $GLOBALS['wp_user'] and $GLOBALS['db'])
{
	// Connexion
	$GLOBALS['connect'] = new mysqli($GLOBALS['wp_server'], $GLOBALS['wp_user'], $GLOBALS['wp_pwd'], $GLOBALS['db']);

	// Si pas de connexion on affiche pour google une indisponibilité
	if($GLOBALS['connect']->connect_errno){
		header($_SERVER['SERVER_PROTOCOL']." 503 Service Unavailable");
		exit($GLOBALS['connect']->connect_error);
	}

	// Pour un bon encodage dans les sorties de la page
	if($GLOBALS['wp_charset']) $GLOBALS['connect']->query("SET NAMES '".$GLOBALS['wp_charset']."'");
}

//--------------------------------------------
// DEBUT DU TRAITEMENT D'EXPORT
//--------------------------------------------

// On selectionne les posts
$fromDate = '2019-06-01 00:00:00';

$sql ="SELECT SQL_CALC_FOUND_ROWS ".$tp.".id, ".$tp.".* FROM ".$tp;
$sql.=" WHERE (".$tp.".post_type='post') ";
$sql.=" AND post_date >= '".$fromDate."'";
$sql.=" ORDER BY post_date DESC";

print $sql.'<br>';

$sel_fiche = $connect->query($sql);

$i = 0;
$num_total = $connect->query("SELECT FOUND_ROWS()")->fetch_row()[0];// Nombre total de fiche

'Nombre d\'articles importés : '.$num_total;

$articles = array();

$script_export = "REPLACE INTO `".$GLOBALS['db_prefix']."content` (`state`, `lang`, `robots`, `type`, `tpl`, `url`, `title`, `description`, `content`, `user_update`, `date_update`, `user_insert`, `date_insert`) VALUES\r\n";

while($res_fiche = $sel_fiche->fetch_assoc())
{
    ++$i;
    print '<hr><h1 style="margin-bottom: 0;">' . $res_fiche['post_title'] .'</h1>';
    print '📅 '.$res_fiche['post_date'].'<br>';
    print '🔗 <a href="'.$res_fiche['guid'].'" target="_blank">'.$res_fiche['guid'].'</a>';

    //-- début traitement statut du post
        if($res_fiche['post_status'] == 'publish') $state = 'active'; else $state = 'desactivate';
    //-- fin


    //-- début traitement url du post
        if(!empty($res_fiche['post_name'])) $url = $res_fiche['post_name']; else $url = make_url($res_fiche['post_title']);
    //-- fin


    //-- début Traitement du visuel de l'article
        print '<h2>Téléchargement des images</h2>';

        print '<h3>visuel de l\'article</h3>';
        $sql_visuel = "SELECT SQL_CALC_FOUND_ROWS ".$tp.".guid as visuel, ".$tp.".* FROM ".$tp;
        $sql_visuel.= " WHERE (".$tp.".post_type='attachment') AND ".$tp.".post_parent=".$res_fiche['ID'];

        $res_visuel = $connect->query($sql_visuel)->fetch_assoc();

        $content_fiche = array();
        $visuel='';  

        print '<pre>';
        if(isset($res_visuel['visuel']) && !empty($res_visuel['visuel']))
        {

            $dirname = pathinfo($res_visuel['visuel'])['dirname'];
            $basename = urlencode(pathinfo($res_visuel['visuel'])['filename']).'.'.pathinfo($res_visuel['visuel'])['extension'];

            $old_dir = $dirname .'/'. $basename;
            $new_dir = 'media/actualites/'.urldecode($basename);

            if(file_exists($new_dir)) {
                print 'ℹ️ ';
            }
            else {

                if( @copy( $old_dir, $new_dir ) )
                    print '✅ ';
                else
                    print '❌ ';

            }

            $visuel=resize($new_dir, 600, null, 'actualites');

            print urldecode($old_dir) .' → '. urldecode($new_dir) .'<br>';

            if(!isset($visuel) || empty($visuel)) $visuel = $new_dir;

            $content_fiche = array (
                'visuel' => $visuel 
            );

            print_r($content_fiche);
            
        }
        else 
        {
            print 'ℹ️ pas de visuel associé';
        }
        print '</pre>';
    //-- fin traitement visuel


    //-- début traitement contenu du post
        
        $json_fiche = '';

        print '<h2>Ancienne DOM de l\'article</h2>';
        print '<code>'.@htmlspecialchars($res_fiche['post_content']).'</code>';

        print '<h3>contenu de l\'article</h3>';
        print '<pre>';
        if(isset($res_fiche['post_content']) and !empty($res_fiche['post_content'])) 
        {

            $post_content = $res_fiche['post_content'];

            $post_content = preg_replace("/\r\n|\r|\n/", '<br/>', $post_content); //echappement retours lignes
            //$post_content = htmlentities($post_content,ENT_IGNORE,'UTF-8');

            //print '<pre>'.htmlspecialchars($post_content).'</pre>';

    
            $dom_content = new DOMDocument;
            $dom_content->loadHTML($post_content);
            
            if(count($dom_content->getElementsByTagName('img')) == 0 ) print 'ℹ️ aucun visuel trouvé dans la dom de l\'article';

            foreach ($dom_content->getElementsByTagName('img') as $img) {

                if($img->hasAttribute('src')) {

                    $dirname = pathinfo($img->getAttribute('src'))['dirname'];
                    $basename = urlencode(utf8_decode(pathinfo($img->getAttribute('src'))['filename'])).'.'.pathinfo($img->getAttribute('src'))['extension'];

                    $old_dir = $dirname .'/'. $basename;
                    $new_dir = 'media/actualites/'.urldecode($basename);

                    // réécriture des tailles
                    $new_width = 637;
                    

                    if(file_exists($new_dir)) {
                        print 'ℹ️ ';
                    }
                    else 
                    {
                        if( @copy ($old_dir, $new_dir))
                        {
                            // suppression de la hauteur
                            if($img->hasAttribute('width')  && $img->getAttribute('width') > $new_width )  
                            {    
                                $new_dir=resize($new_dir, $new_width, null, 'actualites');

                                $post_content = str_replace(
                                    'width="'.$img->getAttribute('width').'"',
                                    'width="'.$new_width.'"',
                                    $post_content);
                            }

                            // suppression de la hauteur
                            if($img->hasAttribute('height'))  
                            {
                                $post_content = str_replace(
                                    'height="'.$img->getAttribute('height').'"',
                                    '',
                                    $post_content);
                            }

                            print '✅ ';
                        }
                        else
                            print '❌ ';

                    }

                    print urldecode($old_dir) .' → '. urldecode($new_dir) .'<br>';

                    $post_content = str_replace(urldecode($old_dir),urldecode($new_dir),$post_content);

                }

            }
    
            $content_fiche = array_merge (
                $content_fiche, 
                array (
                        'title' => $res_fiche['post_title'],
                        'txt-intro' => $res_fiche['post_excerpt'],
                        'texte' => $post_content
                    )
            );

            $json_fiche = json_encode($content_fiche, JSON_UNESCAPED_UNICODE);
        }
        else 
        {
            print 'ℹ️ aucun visuel trouvé dans la dom de l\'article';
        }
        
        print '</pre>';

        print '<h2>Nouvelle DOM de l\'article</h2>';
        print '<code>'.@htmlspecialchars($post_content).'</code>';
        
        
    //-- fin traitement contenu du post


    //-- alimentation du tableau global (surement temporaire)
    $article = array(
        //'id' => $res_fiche['ID'],
        'state' => '\''.$state.'\'',
        'lang' => '\'fr\'',
        'robot' => '\'\'',
        'type' => '\'article\'',
        'tpl' => '\'article\'',
        'url' => '\''.$url.'\'',
        'title' => '\''.$connect->real_escape_string($res_fiche['post_title']).'\'',
        'description' => '\''.$res_fiche['post_excerpt'].'\'',
        'content' => '\''.$connect->real_escape_string($json_fiche).'\'',
        'user_update' => 1,
        'date_update' => '\''.$res_fiche['post_modified'].'\'',
        'user_insert' => 1,
        'date_insert' => '\''.$res_fiche['post_date'].'\''
    );

    //-- fin alimentation tableau

    
    //-- construction de la requete 
    //(1, 'active', 'fr', '', 'page', 'home', 'index', 'Enrdurables', '', '{\"txt-bg-intro\":\"Bienvenue sur le site Énergies durables\",\"txt-intro\":\"<div>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Porro ad reprehenderit omnis, explicabo culpa autem repellendus aut doloribus nihil sequi unde fugit. Ipsum saepe eos harum deleniti corrupti minus ut. Lorem ipsum dolor, sit amet consectetur adipisicing elit. Porro ad reprehenderit omnis, explicabo culpa autem repellendus aut doloribus nihil sequi unde fugit. Ipsum saepe eos harum deleniti corrupti minus ut. <\\/div><div><br><\\/div><div>Lorem ipsum dolor, sit amet consectetur <b>adipisicing elit. Porro ad<\\/b> reprehenderit omnis, explicabo culpa autem repellendus aut doloribus nihil sequi unde fugit. Ipsum saepe eos harum deleniti corrupti minus ut. Lorem ipsum dolor, sit amet consectetur adipisicing elit. Porro ad reprehenderit omnis, explicabo culpa autem repellendus aut doloribus nihil sequi unde fugit. Ipsum saepe eos harum deleniti corrupti minus ut. Lorem ipsum dolor, sit amet consectetur adipisicing elit. Porro ad reprehenderit omnis, explicabo culpa autem repellendus aut doloribus nihil sequi unde fugit. Ipsum saepe eos harum deleniti corrupti minus ut. <\\/div><div><br><\\/div><div><b>Lorem ipsum dolor,<\\/b> sit amet consectetur adipisicing elit. Porro ad reprehenderit omnis, explicabo culpa autem repellendus aut doloribus nihil sequi unde fugit. Ipsum saepe eos harum deleniti corrupti minus ut. <\\/div>\",\"titre-energie\":\"Accès par énergie<br>\",\"nom-energie-1\":\"de récupération<br>\",\"nom-energie-2\":\"bois énergie<br>\",\"nom-energie-3\":\"hydroélectricité\",\"nom-energie-4\":\"éolien terrestre<br>\",\"nom-energie-5\":\"photovolotaïque\",\"nom-energie-6\":\"énergies marines<br>\",\"titre-qui-sommes-nous\":\"Qui sommes-nous ?<br>\",\"txt-qui-sommes-nous\":\"Lorem ipsum dolor, sit amet consectetur <b>adipisicing elit<\\/b>. Porro ad reprehenderit omnis, explicabo culpa autem repellendus aut doloribus nihil sequi unde fugit. Ipsum saepe eos harum deleniti corrupti minus ut. Lorem ipsum dolor, sit amet consectetur adipisicing elit. Porro ad reprehenderit omnis, explicabo culpa autem repellendus aut doloribus nihil sequi unde fugit. Ipsum saepe eos harum deleniti corrupti minus ut. Lorem ipsum dolor, sit amet consectetur adipisicing elit. Porro ad reprehenderit omnis, explicabo culpa autem repellendus aut doloribus nihil sequi unde fugit. Ipsum saepe eos harum deleniti corrupti minus ut.<br><br><b>Lorem ipsum dolor, sit amet consectetur adipisicing elit<\\/b>. Porro ad reprehenderit omnis, explicabo culpa autem repellendus aut doloribus nihil sequi unde fugit. Ipsum saepe eos harum deleniti corrupti minus ut. \",\"titre-actualites\":\"Actualités\",\"img-energie-1\":\"media\\/resize\\/de-recuperation-x1demi-125-140x140.png?zoom=media\\/de-recuperation-x1demi-125.png&1611066536\",\"img-energie-2\":\"media\\/resize\\/bois-energie-x1demi-125-140x140.png?zoom=media\\/bois-energie-x1demi-125.png&1611067669\",\"img-energie-3\":\"media\\/resize\\/hydroelectricite-x1demi-125-140x140.png?zoom=media\\/hydroelectricite-x1demi-125.png&1611067683\",\"img-energie-4\":\"media\\/resize\\/eolien-terrestre-x1demi-125-140x140.png?zoom=media\\/eolien-terrestre-x1demi-125.png&1611067694\",\"img-energie-5\":\"media\\/resize\\/photovoltaique-x1demi-125-140x140.png?zoom=media\\/photovoltaique-x1demi-125.png&1611067705\",\"img-energie-6\":\"media\\/resize\\/energies-marines-x1demi-125-140x140.png?zoom=media\\/energies-marines-x1demi-125.png&1611067716\",\"bg-intro\":\"media\\/gros-schema-hd-x1-2-1920-469-1920x469.png?1611063074\"}', 1, '2021-01-20 18:01:47', 1, '2021-01-13 11:24:50'),
    $script_export.= "(". implode( ",", $article )  .")";
    if($i<$num_total) $script_export.= ",";
    $script_export.="\r\n"; 
    
    

    //-- fin construction de la requete

    //print '<pre style="max-width: 100%;">('.htmlspecialchars(implode( ",", $article )).')</pre>';

}

//-- Début ecriture fichier sql
    $script_sql = fopen('script_export.sql', 'w');
    $script_sql = fopen ('script_export.sql', 'c+b');
    fwrite($script_sql, $script_export);
//-- Fin écriture fichier sql

?>

