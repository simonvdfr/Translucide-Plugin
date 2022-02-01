<?php
include_once("../../api/function.php");
include_once("../../config.php");

$xml = simplexml_load_file('export.xml','SimpleXMLElement', LIBXML_NOCDATA);//
$array = json_decode(json_encode((array)$xml), TRUE);

//highlight_string(print_r($array['posts']['post'], true)); exit;


/*
[title] => Consommer local n'est pas un danger, c'est une nécessité !
[slug] => 2016/09/consommer-local-n-est-pas-un-danger-c-est-une-necessite.html
[tags] => Regard critique,Propositions
[created_at] => 2016-09-09T12:07:00+02:00
[published_at] => 2016-09-09T10:06:51+02:00
[modified_at] => 2016-09-09T15:47:05+02:00
[author] => Benoît Thévard => id = 2
[content]
*/


$i = 0;
$num_total = count($array['posts']['post']);// Nombre total de fiche

'Nombre d\'articles importés : '.$num_total;

$script_export = "REPLACE INTO `".$GLOBALS['db_prefix']."content` (`state`, `lang`, `robots`, `type`, `tpl`, `url`, `title`, `description`, `content`, `user_update`, `date_update`, `user_insert`, `date_insert`) VALUES\r\n";

foreach($array['posts']['post'] as $key => $val) 
{
    ++$i;
    $content_fiche = array();
    $json_fiche = '';

    print '<hr><h1 style="margin-bottom: 0;">' . $val['title'] .'</h1>';
    print '📅 '.$val['created_at'].'<br>';
    print '🔗 <a href="'.$val['slug'].'" target="_blank">'.$val['slug'].'</a>';

    $state = 'active';

    $url = make_url($val['slug']);


    //-- début traitement contenu du post

    //print '<h2>Ancienne DOM de l\'article</h2>';
    //print '<code>'.@htmlspecialchars($val['content']).'</code>';

    print '<h3>contenu de l\'article</h3>';
    print '<pre>';
    if(isset($val['content']) and !empty($val['content'])) 
    {

        $content = $val['content'];

        $content = preg_replace("/\r\n|\r|\n/", '<br/>', $content); //echappement retours lignes
        //$content = htmlentities($content,ENT_IGNORE,'UTF-8');

        //print '<pre>'.htmlspecialchars($content).'</pre>';


        $dom_content = new DOMDocument;
        $dom_content->loadHTML($content);
        
        if(count($dom_content->getElementsByTagName('img')) == 0 ) print 'ℹ️ aucun visuel trouvé dans la dom de l\'article';

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

                if(file_exists($chemin.$dest_img)) print 'ℹ️ ';
                else 
                {
                    if(copy($source_img, $chemin.$dest_img))
                    {
                        // suppression de la hauteur
                        if($img->hasAttribute('width')  && $img->getAttribute('width') > $new_width )  
                        {    
                            //@todo voir pour faire marcher le resize
                            $chemin.$dest_img = resize($chemin.$dest_img, $new_width, null, 'article');

                            $content = str_replace(
                                'width="'.$img->getAttribute('width').'"',
                                'width="'.$new_width.'"',
                                $content);
                        }

                        // suppression de la hauteur
                        if($img->hasAttribute('height'))  
                        {
                            $content = str_replace(
                                'height="'.$img->getAttribute('height').'"',
                                '',
                                $content);
                        }

                        print '✅ ';
                    }
                    else
                        print '❌ ';

                }

                echo'<a href="'.($source_img).'" target="_blank">'.($source_img).'</a> → '.($dest_img).'<br>';

                $content = str_replace(($source_img), ($dest_img), $content);
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
        print 'ℹ️ aucun contenu dans l\'article';
    
    
    print '</pre>';

    print '<h2>Nouvelle DOM de l\'article</h2>';
    print '<code>'.@htmlspecialchars($content).'</code>';
    
        
    //-- fin traitement contenu du post


    //-- alimentation du tableau global (surement temporaire)
    $article = array(
        'id' => '10'.$key,
        'state' => '\''.$state.'\'',
        'lang' => '\'fr\'',
        'robot' => '\'\'',
        'type' => '\'article\'',
        'tpl' => '\'article\'',
        'url' => '\''.$url.'\'',
        'title' => '\''.$val['title'].'\'',
        'description' => '\'\'',
        'content' => '\''.($json_fiche).'\'',
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

    //print '<pre style="max-width: 100%;">('.htmlspecialchars(implode( ",", $article )).')</pre>';

}

//echo $script_export;

//-- Début ecriture fichier sql
    //$script_sql = fopen('script_export.sql', 'w');
    //$script_sql = fopen('script_export.sql', 'c+b');
    //fwrite($script_sql, $script_export);
//-- Fin écriture fichier sql

?>

