<?
// @todo
// on stocke dans la table meta la dernière date de maj, pour ne pas tout regne. on regne que ce qui a été changer depuis la date => complexe car on doit regene les listes de page connexe sans savoir le nombre total d'articles dans les listes
// Ajouter une action quand l'utilisateur supprime une page pour supprimer aussi le fichier sur le serveur


/*
***** Notice ******
- Ajouter dans config.php => $GLOBALS['static_dir'] = 'static';
Pour stocker les fichiers générer dans un sous-dossier pour être clean.


- Ajouter dans le footer le pointage vers ce fichier
<? include("theme/".$GLOBALS['theme']."/admin/static-generator.php"); ?>


- Dans le htaccess rendre fonctionnelles les lignes suivantes :

## Pour charger le index.html statique s'il existe, sinon execution de index.php
DirectoryIndex static/index.html index.php

## Charche le fichier en cache s'il existe
RewriteCond %{DOCUMENT_ROOT}/static/$1.html -f
RewriteRule (.*) /static/$1.html [L]
*/


function rrmdir($dir)
{
	if(is_dir($dir))
	{
		$files = array_diff(scandir($dir), array('.','..'));

		foreach($files as $file) {
			(is_dir("$dir/$file") and !is_link("$dir/$file")) ? rrmdir("$dir/$file") : unlink("$dir/$file");
		}

		return rmdir($dir);
	}
}

switch(@$_REQUEST['mode'])
{
	case"generate":
		include_once($_SERVER['DOCUMENT_ROOT'].'/config.php');// Les variables si on ajax
		include_once($_SERVER['DOCUMENT_ROOT'].'/api/function.php');// Les fonctions si on ajax
		include_once($_SERVER['DOCUMENT_ROOT'].'/api/db.php');// Connexion à la db

		$lang = get_lang();// Sélectionne  la langue
		load_translation('api');// Chargement des traductions du système

		login('high', 'edit-page');// Vérifie que l'on a le droit d'éditer les contenus


		$url = $_POST['url'];

		$dir = (@$GLOBALS['static_dir']?$GLOBALS['static_dir'].'/':'');


		// Crée le dossier pour les fichiers statique s'il y en a un
		if($dir) @mkdir($_SERVER['DOCUMENT_ROOT'].$GLOBALS['path'].$dir, 0755, true);

		// L'url contien un dossier ?
		//echo $url."<br>"; highlight_string(print_r($url), true);
		if(strstr($url, "/"))
		{
			$pathinfo = pathinfo($url);
			@mkdir($_SERVER['DOCUMENT_ROOT'].$GLOBALS['path'].$dir.$pathinfo['dirname'], 0755, true);
		}


		// Chemin du fichier destination
		$file = $_SERVER["DOCUMENT_ROOT"].$GLOBALS['path'].$dir.$url.'.html';

		// Supprime le .html statique si pas de suppression globale
		if(!isset($GLOBALS['static_dir'])) @unlink($file);


		// Génération en php
		// Récupération du contenu de la page
		$html = curl($GLOBALS['home'].($url=='index'?'':$url));

		// Encodage du contenu html
		$html = mb_convert_encoding($html, 'UTF-8', 'auto');

		// Création du fichier avec le html
		file_put_contents($file, $html.'<!-- STATIC '.date('d-m-Y H:i:s').' -->');//time().


		?>
		<script>
			// Fin de la génération on affiche la progression
			$("#progress").css({"opacity":"1", "width":"<?=(int)($_POST['key']*100/$_POST['max'])?>%"});
		</script>
		<?
	break;


	case"list":

		include_once($_SERVER['DOCUMENT_ROOT'].'/config.php');// Les variables si on ajax
		include_once($_SERVER['DOCUMENT_ROOT'].'/api/function.php');// Les fonctions si on ajax
		include_once($_SERVER['DOCUMENT_ROOT'].'/api/db.php');// Connexion à la db

		$lang = get_lang();// Sélectionne  la langue
		load_translation('api');// Chargement des traductions du système

		login('high', 'edit-page');// Vérifie que l'on a le droit d'éditer les contenus

		$num_article = 0;
		$page_article = null;

		echo'<div class="dialog-static-generator none" title="'.__("Génération du site statique").'"><ul id="page-list" class="mtn mbs pls">';


			$sel = $connect->query("SELECT title, state, type, tpl, url, date_update FROM ".$GLOBALS['tc']." WHERE lang='".$lang."' AND state='active' ORDER BY date_update DESC");//date_update DESC
			while($res = $sel->fetch_assoc()) 
			{
				echo'<li title="'.$res['date_update'].' - '.$res['tpl'].'" data-url="'.$res['url'].'"><b>'.$res['title'].'</b> /'.$res['url'].'</li>';

				// Si article, on les comptes pour la nav par page qui les listes
				if($res['type'] == 'article') ++$num_article;

				// Nom de la page qui liste les articles
				if($res['tpl'] == 'article-liste') $page_article = $res['url'];
			}


			// Si plusieur page d'article
			if($page_article and $num_article > $num_pp)
			{
				$num_page = ceil($num_article/$num_pp);

				// On génère à partir de la page 2
				for($i=2; $i<=$num_page; $i++)
				{
					echo'<li title="" data-url="'.make_url($page_article, array("page" => $i)).'">/'.$page_article.'/page_'.$i.'</li>';
				}
			}


			// Génère les pages de tag
			if($page_article)
			{
				$sel = $connect->query("SELECT * FROM ".$GLOBALS['tt']." WHERE lang='".$lang."' AND zone='".$page_article."'");
				while($res = $sel->fetch_assoc()) 
				{			
					if(isset($tags[$res['encode']])) $tags[$res['encode']]++;// Compte le nombre d'article avec le tag
					else 
					{
						echo'<li title="" data-url="'.make_url($page_article, array("" => $res['encode'])).'">/'.$page_article.'/'.$res['encode'].'</li>';

						$tags[$res['encode']] = 1;
					}
				}
			}


			// Si plusieur page pour un tag
			if(isset($tags))
			foreach($tags as $cle => $val)
			if($val > $num_pp)
			{
				$num_page = ceil($val/$num_pp);

				// On génère à partir de la page 2
				for($i=2; $i<=$num_page; $i++)
				{
					echo'<li title="" data-url="'.make_url($page_article, array_merge(array($cle => $cle), array("page" => $i))).'">/'.$page_article.'/'.$cle.'/page_'.$i.'</li>';
				}
			}


		echo"</ul></div>";


		// S'il y a un dossier pour contenir tous les fichiers statiques on le supprime pour bien nettoyer les fichiers qui trainent
		if(isset($GLOBALS['static_dir'])) rrmdir($_SERVER['DOCUMENT_ROOT'].$GLOBALS['path'].$GLOBALS['static_dir']);


		// Date de dernière génération
		//$connect->query("REPLACE INTO `".$tm."` SET type='static', cle='fr', val=NOW()");

		//echo $connect->error;

		?>
		<script>
			function generate(key)
			{
				// Affichage de la progression de la génération

				// Generation
				$.ajax({
					type: "POST",
					url: path+"theme/<?=@$GLOBALS['theme']?>/admin/static-generator.php?mode=generate",
					data: {
						"key": key,
						"max": max,
						"url": page_list[key],
						"nonce": $("#nonce").val()
					},
					success: function(html)
					{
						$("body").prepend(html);
						
						if(page_list[(key+1)]) generate(key+1);// Si encore des pages a générer
						else 
						{	
							// plus de page a générer
							
							$("#static i").removeClass("fa-spin fa-cog").addClass("fa-file-code");
							
							$(".dialog-static-generator").remove();

							setTimeout(function() {
								$("#progress").css({"opacity":"0"});
								setTimeout(function() { $("#progress").css({"width":"0"});}, 1000);	
							}, 1000);
						}
					}
				});
			}


			$(function()
			{
				page_list = {};

				// Liste des pages a générer
				$("#page-list li").each(function(key, val)
				{	
					page_list[key] = $(val).data("url");	

					max = key;				
				});

				$("#static i").removeClass("fa-file-code").addClass("fa-spin fa-cog");

				// Generation de la première page
				generate(0);
				
			});
		</script>
		<?

		exit;
		
	break;

	default:
		?>
		<script>
			// Action si on lance le mode d'edition
			edit.push(function()
			{
				// Générateur de site statique

				// Ajout du bouton
				$("#admin-bar").append("<button id='static' class='fl mat small o50 ho1 t5' title='Génération du site statique'><span class='noss'>Génération</span> <i class='fa fa-fw fa-file-code big'></i></button>");

				// Action sur le bouton 
				$("#static").click(function(event)
				{					
					$.ajax({
						type: "POST",
						url: path+"theme/<?=$GLOBALS['theme']?>/admin/static-generator.php?mode=list",
						data: {
							"nonce": $("#nonce").val()
						},
						success: function(html){
							$("body").prepend(html);
						}
					});
				});

			});
		</script>
	<?
	break;
}
?>