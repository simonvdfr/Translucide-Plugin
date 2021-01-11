<?php if(!$GLOBALS['domain']) exit;?>

<footer>


</footer>


<style>
	/* Barre de cookie */
	#cnilcookie {
		border: none;
		position: fixed;
	    left: 0;
	    right: 0;
	    bottom: 25px;
	    text-align: center;
	    font-size: 1.4rem;
	    display: none;
	}
		#cnilcookie:hover { opacity: 1 !important; }
		#cnilcookie .bt {
			padding: .3rem .8rem;
			border: none;
		}
</style>


<script>
$(function()
{
	// BULLE D'INFORMATION SUR L'UTILISATION DES COOKIES
	// Si bandeau pas masqué
	if(typeof google_analytics !== 'undefined' && get_cookie('analytics') == '')
	{
		// Ajout du bandeau en bas
		$("body").append("<div id='cnilcookie'><div class='bt'><i class='fa fa-fw fa-bell'></i> Nous utilisons les cookies pour établir des statistiques sur la fréquentation du site. <a href='javascript:void(0)' id='masquer'><u>Masquer</u></a> / <a href='javascript:void(0)' id='desactiver'><u>Désactiver</u></a></div></div>");

		// Au click sur le bandeau
		$("#cnilcookie a").click(function(event){
			// Desactive Analytics
			if(event.currentTarget.id == "desactiver")
			{
				// Ne plus lancer analytics
				set_cookie("analytics", "desactiver", "365");

				// Supprime les cookies analytics
				var cookieNames = ["__utma","__utmb","__utmc","__utmz","_ga","_gat","_gid"]
				for(var i=0; i < cookieNames.length; i++) set_cookie(cookieNames[i], '', '0');
			}
			else set_cookie("analytics", "hide", "365");// Masque définitivement la barre

			// Masque la barre
			$("#cnilcookie").fadeOut();

			return false;
		});

		// Affichage du message après un délai
		$("#cnilcookie").delay(2000).fadeTo("slow", 0.8);
	}
});
</script>