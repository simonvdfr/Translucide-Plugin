<?
@include_once("../config.php");// Les variables
@include_once("function.php");// Fonction

$lang = get_lang();// Sélectionne la langue
load_translation('api');// Chargement des traductions du système


// https://developers.facebook.com/apps/
$GLOBALS['facebook_api_id'] = '';
$GLOBALS['facebook_api_secret'] = '';
$GLOBALS['facebook_page'] = '';// https://www.facebook.com/***
$GLOBALS['facebook_jssdk'] = false;

// https://console.developers.google.com/apis/credentials/oauthclient => Application Web
$GLOBALS['google_api_id'] = '';
$GLOBALS['google_api_secret'] = '';
$GLOBALS['google_map'] = '';
$GLOBALS['google_analytics'] = '';
$GLOBALS['google_verification'] = '';
$GLOBALS['google_page'] = '';// https://plus.google.com/***

// https://developer.yahoo.com/apps/
$GLOBALS['yahoo_api_id'] = '';
$GLOBALS['yahoo_api_secret'] = '';

// https://account.live.com/developers/applications/create
$GLOBALS['microsoft_api_id'] = '';
$GLOBALS['microsoft_api_secret'] = '';


switch($_GET['mode'])
{	
	case 'config-user':
		?>
		<?if($GLOBALS['facebook_api_secret']){?><div class="mbt"><label class="w100p tr mrt" for="facebook"><?_e("Facebook id")?></label> <input type="text" id="oauth[facebook]" value="<?=$oauth['facebook']?>" class="w60 small search_user_id"></div><?}?>

		<?if($GLOBALS['google_api_secret']){?><div class="mbt"><label class="w100p tr mrt" for="google"><?_e("Google id")?></label> <input type="text" id="oauth[google]" value="<?=$oauth['google']?>" class="w60 small search_user_id"></div><?}?>

		<?if($GLOBALS['yahoo_api_secret']){?><div class="mbt"><label class="w100p tr mrt" for="yahoo"><?_e("Yahoo id")?></label> <input type="text" id="oauth[yahoo]" value="<?=$oauth['yahoo']?>" class="w60 small search_user_id"></div><?}?>

		<?if($GLOBALS['microsoft_api_secret']){?><div class="mbs"><label class="w100p tr mrt" for="microsoft"><?_e("Microsoft id")?></label> <input type="text" id="oauth[microsoft]" value="<?=$oauth['microsoft']?>" class="w60 small search_user_id"></div><?}?>
		<?
	break;


	case "config":
		/*
		<li class="mtm bold"><?_e("System login third");?></li>

		<li class="mts">
			<label class="w30"><i class="fa fa-fw fa-facebook-f"></i> <?_e("Id of the app facebook");?></label> <input type="text" id="facebook_api_id" placeholder="" class="w60 vatt">
			<a href="https://developers.facebook.com/apps/" target="_blank"><i class="fa fa-fw fa-info-circle mts vam"></i></a>
		</li>
		<li><label class="w30"><?_e("Secret key of the app facebook");?></label> <input type="text" id="facebook_api_secret" placeholder="" class="w60 vatt"></li>

		<li class="mts">
			<label class="w30"><i class="fa fa-fw fa-google"></i> <?_e("Id of the app google");?></label> <input type="text" id="google_api_id" placeholder="" class="w60 vatt">
			<a href="https://console.developers.google.com/apis/credentials/oauthclient" target="_blank"><i class="fa fa-fw fa-info-circle mts vam"></i></a>
		</li>
		<li><label class="w30"><?_e("Secret Key to google app");?></label> <input type="text" id="facebook_api_secret" placeholder="" class="w60 vatt"></li>

		<li class="mts">
			<label class="w30"><i class="fa fa-fw fa-yahoo"></i> <?_e("Id of the app yahoo");?></label> <input type="text" id="yahoo_api_id" placeholder="" class="w60 vatt">
			<a href="https://developer.yahoo.com/apps/" target="_blank"><i class="fa fa-fw fa-info-circle mts vam"></i></a>
		</li>
		<li><label class="w30"><?_e("Secret key to the app yahoo");?></label> <input type="text" id="yahoo_api_secret" placeholder="" class="w60 vatt"></li>

		<li class="mts">
			<label class="w30"><i class="fa fa-fw fa-windows"></i> <?_e("Id of the app microsoft");?></label> <input type="text" id="microsoft_api_id" placeholder="" class="w60 vatt">
			<a href="https://account.live.com/developers/applications/create" target="_blank"><i class="fa fa-fw fa-info-circle mts vam"></i></a>
		</li>
		<li><label class="w30"><?_e("Secret key of microsoft app");?></label> <input type="text" id="microsoft_api_secret" placeholder="" class="w60 vatt"></li>
		*/
	break;	


	case "select-login-mode":
		// @todo: si la page est appelée directement (ajax.php), charger un fond et charger la dialog
		?>
		<div id="dialog-connect" title="<?_e("Log in");?>">

			<style>
				/* Font Awesome pour bt connexion */
				.loading:before {
					content: "\e81f" !important;
					animation: fa-spin 2s infinite linear;

					border-right: none !important;
					padding-right: 0 !important;
				}
				.down:before {
					content: "\e81d" !important;
					animation: bounce-light .35s ease 6 alternate;

					border-right: none !important;
					padding-right: 0 !important;
				}				
					@keyframes bounce-light {
						from { transform: translateY(0);}
						to { transform: translateY(-5px);}
					}
			</style>

			<script>
			// S'il y a une fonction de callback
			callback = <?if($_REQUEST['callback']){ echo'"'.encode($_REQUEST['callback'], "_").'"';} else echo"null";?>;

			// Connexion
			login = function(login_api) 
			{
				bt = $("#dialog-connect a.bt.connect."+login_api);
				
				// FadeOut les autres boutons
				$("#dialog-connect a.bt.connect:not(."+login_api+")").slideUp();
				
				if(login_api != 'internal')// On utilise une api tiers pour la connexion => popup
				{
					// Change l'icône en loading
					$(bt).addClass("loading");
					
					// Affichage du message
					$("#dialog-connect").append("<div class='tc small'>"+ __("Validate the connection in the popup") +"</div>");

					// Création d'un popup qui charge le site de connexion tierce
					width = 420;
					height = 510;
					window.open("<?=$GLOBALS['path']?>api/ajax.php?mode=external-login&login_api="+login_api, "popup_connect", "top="+((screen.height / 2) - (height / 2))+", left="+((screen.width / 2) - (width / 2))+", width="+width+", height="+height+", location=no, menubar=no, directories=no, status=no, scrollbars=auto");
				}
				else// On utilise le système de login interne
				{
					// Unbind le click
					$(bt).attr("href","javascript://").css("cursor","default");

					// Change l'icône en flèche vers le bas
					$(bt).addClass("down");

					// Supprime les css :hover
					$(bt).addClass("nohover");

					// Injection du formulaire de login en dessous du bt
					$.ajax({url: "<?=$GLOBALS['path']?>api/ajax.php?mode=internal-login"}).done(function(html) { $("#dialog-connect").append(html); });
				}
			};
			</script>		

			<?if($_REQUEST['msg']){?>
				<div class="mas mtn pat ui-state-highlight"><?=htmlspecialchars($_REQUEST['msg']);?></div>
			<?}?>

			<a href="javascript:login('internal');void(0);" class="bt connect internal short"><?_e("Connection with");?> <?=$_SERVER['HTTP_HOST'];?></a>

			<?if($GLOBALS['facebook_api_secret']){?><a href="javascript:login('facebook');void(0);" class="bt connect facebook"><?_e("Connection with");?> Facebook</a><?}?>

			<?if($GLOBALS['google_api_secret']){?><a href="javascript:login('google');void(0);" class="bt connect google"><?_e("Connection with");?> Google</a><?}?>

			<?if($GLOBALS['yahoo_api_secret']){?><a href="javascript:login('yahoo');void(0);" class="bt connect yahoo"><?_e("Connection with");?> Yahoo</a><?}?>
			
			<?if($GLOBALS['microsoft_api_secret']){?><a href="javascript:login('microsoft');void(0);" class="bt connect microsoft"><?_e("Connection with");?> Hotmail - Microsoft</a><?}?>

		</div>
		<?
	break;




	case "external-login":// external_token : utilisation de systèmes de connexion tierce, se déroule dans une Popup
		
		// @todo: ajouter un mode pour ajouter un login silencieux, juste pour avoir les tokens tiers
		// @todo: Twitter, Instagram, Flicker

		// Vérifie que l'on a sélectionné un système tiers
		if($_REQUEST['login_api']) $login_api = $_SESSION['login_api'] = encode($_REQUEST['login_api']);
		else exit(false);

		// Variable générique
		$redirect_uri = $GLOBALS['home']."api/ajax.php?mode=external-login&login_api=";

		if(!isset($_REQUEST["code"])) nonce('state');// CSRF protection

		
		
		// FACEBOOK params &scope=user_photos

		$get_code['facebook'] = "https://graph.facebook.com/oauth/authorize?client_id=".$GLOBALS['facebook_api_id']."&state=".$_SESSION['state']."&display=popup&redirect_uri=".urlencode($redirect_uri)."facebook";

		$token_return_type['facebook'] = "json";// url

		$get_token['facebook'] = "https://graph.facebook.com/oauth/access_token?client_id=".$GLOBALS['facebook_api_id']."&client_secret=".$GLOBALS['facebook_api_secret']."&code=".(isset($_REQUEST['code'])?$_REQUEST['code']:"")."&redirect_uri=".urlencode($redirect_uri)."facebook";

		$token_params['facebook'] = null;
		
		// https://graph.facebook.com/me/albums?fields=photos&access_token=
		$get_info['facebook'] = "https://graph.facebook.com/me?fields=id,name,picture&access_token=";
		$get_info_uid['facebook'] = "id";



		// GOOGLE params
		
		// Le scope plus.login permet de faire des recherches d'autre utilisateur
		$get_code['google'] = "https://accounts.google.com/o/oauth2/auth?scope=".urlencode("profile https://www.googleapis.com/auth/plus.login")."&state=".$_SESSION['state']."&response_type=code&client_id=".$GLOBALS['google_api_id']."&redirect_uri=".urlencode($redirect_uri)."google";// pour obtenir le code
		
		$token_return_type['google'] = "json";

		$get_token['google'] = "https://accounts.google.com/o/oauth2/token";// pour obtenir le token avec le code

		$token_params['google'] = array(
			"code" => (isset($_REQUEST['code'])?$_REQUEST['code']:""),
			"client_id" => $GLOBALS['google_api_id'],
			"client_secret" => $GLOBALS['google_api_secret'],
			"redirect_uri" => $redirect_uri."google",
			"grant_type" => "authorization_code"
		);

		$get_info['google'] = "https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token=";// pour obtenir id de l'utilisateur
		$get_info_uid['google'] = "id";



		// YAHOO params

		$get_code['yahoo'] = "https://api.login.yahoo.com/oauth2/request_auth?client_id=".$GLOBALS['yahoo_api_id']."&state=".$_SESSION['state']."&response_type=code&redirect_uri=".urlencode($redirect_uri)."yahoo";

		$token_return_type['yahoo'] = "json";

		$get_token['yahoo'] = "https://api.login.yahoo.com/oauth2/get_token";
		$get_token_uid['yahoo'] = "xoauth_yahoo_guid";

		$token_params['yahoo'] = array(
			"code" => (isset($_REQUEST['code'])?$_REQUEST['code']:""),
			"client_id" => $GLOBALS['yahoo_api_id'],
			"client_secret" => $GLOBALS['yahoo_api_secret'],
			"redirect_uri" => $redirect_uri."yahoo",
			"grant_type" => "authorization_code"
		);



		// MICROSOFT params

		$get_code['microsoft'] = "https://oauth.live.com/authorize?response_type=code&client_id=".$GLOBALS['microsoft_api_id']."&state=".$_SESSION['state']."&scope=wl.signin wl.basic&redirect_uri=".urlencode($redirect_uri)."microsoft";

		$token_return_type['microsoft'] = "json";
		
		//https://login.microsoftonline.com/common/oauth2/token
		$get_token['microsoft'] = "https://oauth.live.com/token";
		$get_token_uid['microsoft'] = "user_id";

		$token_params['microsoft'] = array(
			"code" => (isset($_REQUEST['code'])?$_REQUEST['code']:""),
			"client_id" => $GLOBALS['microsoft_api_id'],
			"client_secret" => $GLOBALS['microsoft_api_secret'],
			"redirect_uri" => $redirect_uri."microsoft",
			"grant_type" => "authorization_code"
		);
		
		// https://graph.microsoft.com/v1.0/me?access_token=
		$get_info['microsoft'] = "https://apis.live.net/v5.0/me?access_token=";


		
		// On ouvre l'URL tierse pour récupérer le code
		if($get_code[$login_api] and !isset($_REQUEST["code"])) {			
			header("Location: ".$get_code[$login_api]);
			exit;
		}


		
		// On a le code donc on va chercher le token
		if($login_api and $_REQUEST["code"] and $_SESSION['state'] and $_SESSION['state'] === $_REQUEST['state'])
		{
			// Plus besoin du state, on le supprime
			unset($_SESSION['state']);

			// Récupération du token
			$token_response = curl($get_token[$login_api], $token_params[$login_api]);
			
			// Extraction du token de la réponse
			if($token_return_type[$login_api] == "url") {			
				$tab_token_response = null;
				parse_str($token_response, $tab_token_response);
			}
			else {
				$tab_token_response = json_decode($token_response, true);
			}

			//echo"<br><br><strong>Response</strong> : "; highlight_string(print_r($tab_token_response, true));

			// On a un access_token
			if($tab_token_response['access_token'])
			{
				// On récupère le token tiers
				$_SESSION['access_token_external'][$login_api] = $tab_token_response['access_token'];

				// On récupère l'id tiers s'il se trouve dans le retour avec le access_token
				if(isset($get_token_uid[$login_api])) $uid = $tab_token_response[$get_token_uid[$login_api]];

				// Rapatriement des données de l'user (id, nom...)
				if($get_info[$login_api]) {
					$info_response = json_decode(curl($get_info[$login_api].$tab_token_response['access_token']), true);
					//echo"<br><br><strong>User info</strong> : "; highlight_string(print_r($info_response, true));

					// On récupère l'id tiers s'il se trouve dans les infos
					if($get_info_uid[$login_api]) $uid = $info_response[$get_info_uid[$login_api]];
				}

				// Si on a un access_token tiers on crée un token maison checkable facilement et avec une durée de vie plus longue
				if($uid)
				{
					if(!isset($GLOBALS['connect'])) include_once("db.php");
					
					// On vérifie l'utilisateur
					$uid = $connect->real_escape_string($uid);
					$sel = $connect->query("SELECT * FROM ".$table_user." WHERE oauth LIKE '%\"".$login_api."\":\"".$uid."\"%' AND state='active' LIMIT 1");
					$res = $sel->fetch_assoc();
					
					// L'utilisateur existe et est activé
					if($res['id'])
					{
						// Supprime l'ancienne session
						session_regenerate_id(true);

						if(token($res['id'], $res['email'], $res['auth']))// On crée le token maison
						{
							// Crée le token light pour vérifier si on a le bon mot de passe
							token_light($res['id'], $res['salt']);

							?>
							<script>
								// Quand l'utilisateur ferme la fenêtre ou le js
								window.onunload = function() 
								{
									// S'il y a une fonction de callback à lancer : typiquement l'edition
									if(window.opener.callback) {										
										eval("opener." + window.opener.callback + "()");
									}
								}	
								window.close();								
							</script>
							<?
						}
					}
					else $msg = __("Unknown user")." ".htmlspecialchars($uid);
				}
				else $msg = __("Unable to find the user number");
			}
			else $msg = __("Unable to find the access token");
		}
		else $msg = __("Connection error")." 1";

		if($msg) echo $msg;

		//highlight_string(print_r($_SESSION, true));
		//highlight_string(print_r($_REQUEST, true));

		if(isset($GLOBALS['connect'])) $GLOBALS['connect']->close();

	break;
}
?>