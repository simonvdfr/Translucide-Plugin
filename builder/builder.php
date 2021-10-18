<?php 
// Ajouter un dossier builder/ dans le dossier tpl/
// y déposer des fichiers .php avec des sections éditables

//@todo si choix tpl builder on regarde dans la base de donnée les pages qui l'uilisent et propose de reprendre la tpl
//@todo tous les attributs de l'édition ne sont pas dans les fonctions _event du coup l'edition n'est pas complete lors de l'ajout à la volé d'un élément editable

switch(@$_GET['mode'])
{
	// AFFICHAGE de la page
	default:
		if(!$GLOBALS['domain']) exit;


		// Include les éléments du builder pour affichage
		if(isset($GLOBALS['content']['builder']))
		foreach($GLOBALS['content']['builder'] as $index => $array)
		{
			// init les clé
			$GLOBALS['editkey'] = key($array);

			// Insert l'élément
			include($_SERVER['DOCUMENT_ROOT'] . $GLOBALS['path']."theme/".$GLOBALS['theme']."/tpl/builder/".current($array).".php");

			// pour l'ajout d'élément builder
			if($GLOBALS['editkey'] > $_SESSION['editkey']) 
				$_SESSION['editkey'] = $GLOBALS['editkey'];
		}
		?>

		<script>
		$(function()
		{
			edit.push(function()
			{
				$.ajax(
				{
					type: "POST",
					url: path+"theme/"+theme+"/tpl/builder.php?mode=edit",
					success: function(html){ $("body").append(html); }
				});
			});
		});
		</script>
		<?php
	break;


	// EDITION DES ÉLÉMENTS DU BUILDER
	case "edit":
		include_once("../../../config.php");// Les variables
		//include_once("../../../api/function.php");// Fonction

		if(!isset($_SESSION['editkey'])) $_SESSION['editkey'] = 1;

		?>
		<style>
			main {
				min-height: 500px;
			}

			#builder {
				box-shadow: 0 1px 3px rgb(0 0 0 / 30%);
				background-color: rgba(240, 240, 240, 0.8);
				box-sizing: padding-box;
				text-shadow: none;
				color: #000000;
				font-family: Arial;
				position: fixed;
				bottom: 0;
				width: 100%;
				z-index: 12;
				transition: background-color .3s linear;
				animation: slide-up .3s 1 ease-out;
			}
				#builder li {
					border: 1px dotted rgba(61, 128, 179, 0.2);
					border-radius: 5px;
					display: inline-block;
					/* position: relative;
					overflow: hidden; */
					vertical-align: middle;
					min-width: 70px;
					/* min-height: 70px; */
					background-color: rgba(61, 128, 179, 0.05);
					cursor: cell;/*move*/
					margin: 0.5rem;
					padding: 0.5rem;
				}

				.lucide [data-builder] { position: relative; }

					[data-builder] .fa-cancel { font-size: 1.4rem; }

				.bt-move-builder { 
					position: fixed;
					right: 10px;
					bottom: 10px;
					z-index: 15;
				}
				.move {
					/* transform: scale(.9); */
					border: 2px dashed #3d80b3;
					background-color: aliceblue;
					/* padding: 1rem; */
					position: relative;
				}

				.dragbuilder {
					background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAACAQMAAABIeJ9nAAAABlBMVEUAAAC/v79T5hyIAAAAAXRSTlMAQObYZgAAAAxJREFUCNdjaGBgAAABhACBKN161wAAAABJRU5ErkJggg==') repeat;
					background-color: #9fe1ff;
					position: absolute;
					width: 15px;
					height: 100%;
					top: 0;		
					left: 0;
					/*margin-left: -0.5rem;*/
					cursor: move;
					z-index: 1;
				}
		</style>


		<ul id="builder" class="unstyled tc">
			<?php
			// Liste les elements du builder - boucle dossier builder
			$dir = $_SERVER['DOCUMENT_ROOT'] . $GLOBALS['path']."theme/".$GLOBALS['theme']."/tpl/builder/";
			if(is_dir($dir))// Le dossier existe
			{
				$scandir = array_diff(scandir($dir), array('..', '.'));// Nettoyage
				foreach($scandir as $cle => $filename)
				{
					$pathinfo = pathinfo($filename, PATHINFO_FILENAME);
					echo'<li data-file="'.$filename.'">'.$pathinfo.'</li>';
				}
			}
			?>
		</ul>

		<a href='javascript:move_builder();' class="bt-move-builder" title="Déplacer les éléments">Déplacer <i class='fa fa-fw fa-move big'></i></a>
		

		<script>
			$(function()
			{
				// AJOUT d'un élément
				$("#builder li").on("click", function(event) {
					$.ajax(
					{
						type: "POST",
						url: path+"theme/"+theme+"/tpl/builder.php?mode=add",
						data: {
							"file": $(this).data("file"),
							"nonce": $("#nonce").val()// Pour la signature du formulaire
						},
						success: function(html)
						{
							// Unbind les events d'edition
							$(".editable").off();
							$(".editable-media").off(".editable-media");
							$(".editable-href").off(".editable-href");

							// Insertion du contenu éditable
							$("main").append(html);

							// Joue les animations pour afficher les contenus
							$(".animation").addClass("fire");

							// Contenu editable
							//@todo revoir la fonction editable_event pour y mettre ça
							$(".editable").attr("contenteditable","true");

							// Relance les events d'edition
							editable_event();
							editable_media_event();
							editable_href_event();
						}
					});
				});


				// DÉPLACEMENT
				// Rends déplaçables les éléments
				move_builder = function() {

					// Change le style du bouton et l'action
					$(".fa-move").css("transform","scale(.5)");

					// Désactive l'edition
					$(".editable-media").off(".editable-media");
					$(".editable").off();

					// Change l'action sur le lien 'move'
					$("[href='javascript:move_builder();']").attr("href","javascript:unmove_builder();");

					// Ajout d'une zone de drag pour chaque élément
					$("[data-builder]").prepend("<div class='dragbuilder'></div>");

					// Change le style des éléments déplaçable
					$("[data-builder]").addClass("move");

					// Les rend déplaçable [data-builder]
					$("main").sortable({
						handle: ".dragbuilder",
						items: '.move',
						axis: "y",
						//placeholder: "ui-state-highlight",
						//helper:'clone',
						stop: function(event, ui) {
							tosave();// A sauvegarder
						}
					});

				}

				// Désactive le déplacement des éléments
				unmove_builder = function() {

					// Change le style du bouton et l'action
					$(".bt-move-builder .fa-move").css("transform","scale(1)");

					// Change l'action sur le lien 'move'
					$(".bt-move-builder[href='javascript:unmove_builder();']").attr("href","javascript:move_builder();");

					// Active l'edition
					editable_event();
					editable_media_event();

					// Désactive le déplacement
					//$("main").sortable(); //@todo Vérifie si le sortable est lancer
					$("main").sortable("destroy");

					// Supprime la zone de drag pour chaque élément
					$("[data-builder] .dragbuilder").remove();

					// Change le style des éléments déplaçable
					$("[data-builder]").removeClass("move");
				}


				// SUPPRESSION
				add_remove = function(that) {
					// Ajout de la suppression au survole d'un bloc
					$("[data-builder]").append("<a href='javascript:void(0)' onclick='remove_builder(this)'><i class='fa fa-cancel absolute none red pointer' style='top: 0; right: 0; z-index: 10;' title='"+ __("Remove") +"'></i></a>");
				};

				add_remove();


				// Fonction pour supprimer un bloc
				remove_builder = function(that) {
					$(that).closest("[data-builder]").fadeOut("slow", function() {
						this.remove();
					});
				};


				// SAVE
				// Trouve une clé
				find_key = function(elem)
				{
					// Récupère le numéro de l'element en fonction de son type d'edition
					if($(elem).hasClass("editable") || $(elem).hasClass("editable-media"))
						return $(elem).attr("id").split("-").pop();
					else if($(elem).data("id"))
						return $(elem).data("id").split("-").pop();
					else if($(elem).data("href"))
						return $(elem).data("href").split("-").pop();
				}

				// Avant de récolter les contenus on les nettoie des fonctions admin
				before_data.push(function()
				{
					// Désactive le déplacement avant sauvegarde
					if($("main").hasClass("ui-sortable")) unmove_builder();

					// Supprime de la dom les supp
					$("[onclick='remove_builder(this)']").remove();
				});

				// Crée une liste json des éléments builder pour save
				before_save.push(function()
				{
					// Envoie les datas
					data["content"]["builder"] = {};
					$(document).find(".content [data-builder]").each(function(index, element)
					{
						data["content"]["builder"][index] = {};// index pour l'ordre d'affichage des éléments

						// Clé de l'élément build en cours
						var key = find_key(element);

						// Si c'est un groupe d'élément éditable on cherche la 1er clé d'élément editable
						if(key == undefined)
						{
							//console.log("elem")
							// Récupère le 1er élément editable
							var elem = $(element).find(".editable, editable-media, [data-href], [data-bg]").first();

							// Récupère le numéro de l'element en fonction de son type d'edition
							var key = find_key(elem);
						}
						
						// Ajoute l'élément à la liste du builder avec le bon numéro d'id
						data["content"]["builder"][index][key] = $(element).data("builder");
					});
				});

				// Après la sauvegarde
				before_save.push(function()
				{
					// Remets les options pour supprimer un élément
					add_remove();
				});

			});
		</script>			
		<?php
	break;


	// AJOUT D'UN ÉLÉMENT
	case "add":
		include_once("../../../config.php");// Les variables
		include_once("../../../api/function.php");// Fonction

		// On récupère l’incrémental en cours des id de contenu éditable
		$GLOBALS['editkey'] = (int)$_SESSION['editkey'];

		// Ajoute un élément
		include('builder/'.$_REQUEST['file']);

		// On sauvegarde l'incrémental d'id de contenu editable
		$_SESSION['editkey'] = $GLOBALS['editkey'];

	break;
}
?>