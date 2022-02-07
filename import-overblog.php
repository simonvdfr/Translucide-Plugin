<?php
include_once("../../config.php");
include_once("../../api/function.php");
include_once("../../api/db.php");// Connexion à la db

$xml = simplexml_load_file('export.xml','SimpleXMLElement', LIBXML_NOCDATA);//, LIBXML_NOCDATA
$array = json_decode(json_encode((array)$xml), TRUE);

//highlight_string(print_r($array['posts']['post'], true)); exit;


/*
[title] => 
[slug] => 
[tags] => 
[created_at] => 2016-09-09T12:07:00+02:00
[published_at] => 2016-09-09T10:06:51+02:00
[modified_at] => 2016-09-09T15:47:05+02:00
[author] =>
[content]
*/


$i = 0;
$num_total = count($array['posts']['post']);// Nombre total de fiche

echo 'Nombre d\'articles importés : '.$num_total;

$script_export = "REPLACE INTO `".$GLOBALS['db_prefix']."content` (`id`, `state`, `lang`, `robots`, `type`, `tpl`, `url`, `title`, `description`, `content`, `user_update`, `date_update`, `user_insert`, `date_insert`) VALUES\r\n";

foreach($array['posts']['post'] as $key => $val) 
{
    ++$i;
    $content_fiche = array();
    $json_fiche = '';


    $id = 100+$key;

    // Construction de l'url
    $url = pathinfo($val['slug'], PATHINFO_FILENAME);
    //$url = make_url($val['slug']);

    echo '<hr><h1 style="margin-bottom: 0;">' . $val['title'] .'</h1>';

    echo 'i:'.$i.'/key:'.$key.'/id:'.$id.'<br>';

    if(!is_array($val['tags']))// Tags
    {   
        echo $val['tags'].'<br>';

        $tags = explode(",", $val['tags']);
        foreach($tags as $cle => $tag) {
            //if(isset($tag) and $tag != "") $GLOBALS['connect']->query("REPLACE INTO ".$GLOBALS['tt']." SET id='".$id."', zone='actualites', encode='".encode($tag)."', name='".addslashes(trimer($tag))."', ordre='1'");
        }
    }

    echo '📅 '.$val['created_at'].'<br>';
    echo '🔗 <a href="'.$url.'" target="_blank">'.$url.'</a> | '.$val['slug'];

    $state = 'active';




    //-- début traitement contenu du post

    //echo '<h2>Ancienne DOM de l\'article</h2>';
    //echo '<code>'.@htmlspecialchars($val['content']).'</code>';

    echo '<h3>contenu de l\'article</h3>';
    echo '<pre>';
    if(isset($val['content']) and !empty($val['content'])) 
    {

        $content = $val['content'];

        $content = preg_replace("/\r\n|\r|\n/", '<br/>', $content); //echappement retours lignes
        //$content = htmlentities($content,ENT_IGNORE,'UTF-8');

        //echo '<pre>'.htmlspecialchars($content).'</pre>';


        $dom_content = new DOMDocument;
        @$dom_content->loadHTML($content);
        
        if(count($dom_content->getElementsByTagName('img')) == 0 ) echo 'ℹ️ aucun visuel trouvé dans la dom de l\'article';

        foreach($dom_content->getElementsByTagName('img') as $img) 
        {
            if($img->hasAttribute('src')) 
            {
                //$dirname = pathinfo($img->getAttribute('src'))['dirname'];
                //$basename = urldecode(utf8_decode(pathinfo($img->getAttribute('src'))['filename'])).'.'.pathinfo($img->getAttribute('src'))['extension'];

                //$old_dir = $dirname .'/'. $basename;
                //$new_dir = 'media/article/'.urldecode($basename);

                // Nom du fichier
                $basename = utf8_decode(pathinfo(urldecode($img->getAttribute('src')))['filename']).'.'.pathinfo($img->getAttribute('src'))['extension'];

                // Image source
                $source_img = $img->getAttribute('src');

                // Image destination
                $dest_img = '/media/article/'.urldecode($basename);

                $chemin = '../..';

                // réécriture des tailles
                $new_width = 690;

                $dest_img_big = null;

                if(file_exists($chemin.$dest_img)) echo 'ℹ️ ';
                else 
                {
                    if(copy($source_img, $chemin.$dest_img))
                    {
                        list($source_width, $source_height, $type, $attr) = getimagesize($chemin.$dest_img);

                        // Si l'image est plus grande que la taille max
                        if($img->hasAttribute('width') and $img->getAttribute('width') > $new_width)  
                        {    
                            $dest_img_big = $dest_img;
                            $dest_img = resize($chemin.$dest_img, $new_width, null, 'article');

                            $content = str_replace(
                                'width="'.$img->getAttribute('width').'"',
                                'width="'.$new_width.'"',
                                $content);

                            echo 'width attr > new_width';
                        }
                        // Si l'image original est plus grande que la taille affiché
                        else if($img->hasAttribute('width') and $source_width > $img->hasAttribute('width'))
                        {    
                            $dest_img_big = $dest_img;
                            $dest_img = resize($chemin.$dest_img, $img->hasAttribute('width'), null, 'article');

                            echo 'source_width > width attr';
                        }
                        // Si l'image original est plus grande que la largeur de l'article
                        else if($source_width > $new_width)
                        {    
                            $dest_img_big = $dest_img;
                            $dest_img = resize($chemin.$dest_img, $new_width, null, 'article');

                            echo 'source_width > new_width';
                        }

                        // suppression de la hauteur
                        /*if($img->hasAttribute('height'))  
                        {
                            $content = str_replace(
                                'height="'.$img->getAttribute('height').'"',
                                '',
                                $content);
                        }*/

                        echo ' ✅ ';
                    }
                    else
                        echo ' ❌ ';

                }

                echo'<a href="'.($source_img).'" target="_blank">'.($source_img).'</a> → '.($dest_img).'<br>';

                // les liens vers les grandes images
                if($dest_img_big)
                $content = str_replace('href="'.$source_img, 'href="'.$dest_img_big, $content);

                // les images
                $content = str_replace('src="'.$source_img, 'src="'.$dest_img, $content);

                // sécuritée
                $content = str_replace($source_img, $dest_img, $content);
            }

        }

        $content_fiche = array_merge (
            $content_fiche, 
            array (
                    'title' => $val['title'],
                    'texte' => $content
                )
        );

        $json_fiche = json_encode($content_fiche, JSON_UNESCAPED_UNICODE);
    }
    else 
        echo 'ℹ️ aucun contenu dans l\'article';
    
    
    echo '</pre>';

    //echo '<h2>Nouvelle DOM de l\'article</h2>';
    //echo '<code>'.@htmlspecialchars($content).'</code>';
    
        
    //-- fin traitement contenu du post


    //-- alimentation du tableau global (surement temporaire)
    $article = array(
        'id' => $id,
        'state' => '\''.$state.'\'',
        'lang' => '\'fr\'',
        'robot' => '\'\'',
        'type' => '\'article\'',
        'tpl' => '\'article\'',
        'url' => '\''.$url.'\'',
        'title' => '\''.$GLOBALS['connect']->real_escape_string($val['title']).'\'',
        'description' => '\'\'',
        'content' => '\''.$GLOBALS['connect']->real_escape_string($json_fiche).'\'',
        'user_update' => 2,
        'date_update' => '\''.$val['modified_at'].'\'',
        'user_insert' => 2,
        'date_insert' => '\''.$val['created_at'].'\''
    );

    //-- fin alimentation tableau

    
    //-- construction de la requete 
    //(1, 'active', 'fr', '', 'article', 'tpl', 'url', 'Titre', '', '{\"contenu\"}', 1, '2021-01-20 18:01:47', 1, '2021-01-13 11:24:50'),
    $script_export.= "(". implode( ",", $article )  .")";
    if($i<$num_total) $script_export.= ",";
    $script_export.="\r\n"; 
    
    

    //-- fin construction de la requete

    //echo '<pre style="max-width: 100%;">('.htmlspecialchars(implode( ",", $article )).')</pre>';

    //if($i == 10) break;

}

//echo '<pre>'.htmlspecialchars($script_export).'</pre>';

//$GLOBALS['connect']->query($script_export);

//-- Début ecriture fichier sql
    $script_sql = fopen('script_export.sql', 'w');
    $script_sql = fopen('script_export.sql', 'c+b');
    fwrite($script_sql, $script_export);
//-- Fin écriture fichier sql

?>

