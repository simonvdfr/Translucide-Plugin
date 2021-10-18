<?if(!$GLOBALS['domain']) exit;?>

<!-- Configuration de l'api -->
<section class="mw960p center">

	<h2>récuprétion du token instagram</h2>

	<article>
		<ol>
			<li class="mbm">Suivre les étapes  1 à 3 de la documentation : <a href='https://developers.facebook.com/docs/instagram-basic-display-api/getting-started' target='_blank'>API Basic Display d'Instagram</a></li>
			
			<li class="mbm">
				Renseigner les informations de votre application :

				<?

				//Parametres de l'app
				$params['client_id'] = $client_id = @$_POST['client_id'];
				$params['app_id'] = $app_id = @$_POST['app_id'];
				$params['client_secret'] = $client_secret = @$_POST['client_secret'];
				$params['grant_type'] = $grant_type = 'authorization_code';
				$params['redirect_uri'] = $redirect_uri = @$_POST['redirect_uri'];

				?>

				<form method="post">

					<div class="mtt"><label for="client_id" class="w25">ID de l'APP FB DEV : </label><input type="text" id="client_id" name="client_id" placeholder="client_id" required value="<?if(isset($_POST['client_id'])) echo $_POST['client_id']; else echo $client_id;?>"  ></div>

					<div class="mtt"><label for="app_id" class="w25">ID de l'APP Instagram dans FB DEV : </label><input type="text" id="app_id" name="app_id" placeholder="app_id" required value="<?if(isset($_POST['app_id'])) echo $_POST['app_id']; else echo $app_id;?>" ></div>

					<div class="mtt"><label for="client_secret" class="w25">Clé secrète de l'app Instagram : </label><input type="text" id="client_secret" name="client_secret" placeholder="client_secret" required value="<?if(isset($_POST['client_secret'])) echo $_POST['client_secret']; else echo $client_secret;?>" ></div>

					<div class="mtt"><label for="redirect_uri"  class="w25">URI de redirection OAuth valides : </label><input type="text" id="redirect_uri" name="redirect_uri" placeholder="redirect_uri" required value="<?if(isset($_POST['redirect_uri'])) echo $_POST['redirect_uri']; else echo $redirect_uri;?>" >
						<span class="small">URL hors cms / qui ne redirige pas pour ne pas perdre le code dans l'URL de retour / doit être autorisé dans l'interface de Facebook</span></div>

					<div class="mts"><button type="submit">Obtenir le code</button></div>

				</form>

				<?
				if(
					isset($_POST['app_id']) AND
					isset($_POST['client_id']) AND
					isset($_POST['client_secret']) AND
					isset($_POST['redirect_uri'])
				) 
				{						
					// on ouvre l'url pour obtenir le code
					// https://api.instagram.com/oauth/authorize?
					// client_id={app-id}
					// &redirect_uri={redirect-uri}
					// &scope=user_profile,user_media
					// &response_type=code
	
					$url = 'https://api.instagram.com/oauth/authorize?';
					$url.= 'client_id='.$app_id;
					$url.= '&redirect_uri='.$redirect_uri;
					$url.= '&scope=user_profile,user_media';
					$url.= '&response_type=code';

					?>
					<script type="text/javascript" language="Javascript">window.open("<?=$url?>","_blank");</script>
					<?
				}?>
			
			</li>
			
			<li class="mbm">
			
				Renseigner le code obtenu pour l'échanger contre un token de Page longue durée :

				<form method="post">
					
					<div class="mtt">

						<div class="mbt"> ⚠ <i>Copiez le code (sans #_ portion) afin de l’utiliser à cette étape.</i></div>
						<label for="code" class="w25">Code :</label><input id="code" name="code" placeholder="code" required value="<?if(isset($_POST['code'])) echo $_POST['code'];?>" />
					
					</div>

					<div class="mts"><button type="submit">Obtenir le token</button></div>

				</form>

				<?
				if(isset($_POST['code'])) {

					$params['code'] = $code = $_POST['code'];

					// On génère un token court (étape 5 de la documentation)
					$array = array(
						CURLOPT_URL => 'https://api.instagram.com/oauth/access_token',
						CURLOPT_POST => true,
						CURLOPT_POSTFIELDS => $params
					);

					$ch = curl_init();

					curl_setopt_array($ch, $array);

					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

					$curl_exec = curl_exec($ch);

					curl_close($ch);

					$curl_content = json_decode($curl_exec,true);
					$access_token = $curl_content['access_token'];
					$user_id = $curl_content['user_id'];


					// si token récupéré, on échange le token court contre un long 
					// cf. https://developers.facebook.com/docs/instagram-basic-display-api/guides/long-lived-access-tokens
					if(isset($access_token)) 
					{
					?>
						<div class="mtm mbt">user_id : <?=$user_id?></div>

						<div class="green mtt">✔</span> Token court : </div>

						<textarea class="w50" readonly><?=$access_token?></textarea>
							
						<?
						$ch = curl_init();

						curl_setopt($ch, CURLOPT_URL, "https://graph.instagram.com/access_token?".
													  "grant_type=ig_exchange_token&".
													  "client_secret=".$client_secret.
													  "&access_token=".$access_token);

						curl_setopt($ch, CURLOPT_HEADER, 0);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

						$curl_exec = curl_exec($ch);

						curl_close($ch);

						$curl_content = json_decode($curl_exec,true);

						$_POST['access_token'] = $access_token = $curl_content['access_token'];
						$expire_in = $curl_content['expires_in'];
						
						if(isset($access_token) and isset($expire_in))
						{
							?>
							<div>
								
								<div class="green mtt">✔</span> Token long : </div> 

								<textarea class="w50" readonly><?=$access_token?></textarea>

								<div>
									
									<span class="big">⚠</span> <i>Ce token à une durée d'expiration de </i> <?=$expire_in?> secondes. Il est necessaire de le raffraichir tous les 50 jours par un script installé sur la racine du site et éxécuté par CRON (cf. : instagram.token-refresh.php)
									
								</div>
						
							</div>
							<?
						}
						else {
							?>
							<div style="color: #ff0000;">

								<span class="big">⚠</span> Erreur dans l\'obtention du token long : <?=print_r($curl_exec)?>

							</div>
							<?
						}
						
						
					}
					else {
					?>
						<div style="color: #ff0000;">

							<span class="big">⚠</span> Erreur dans l'obtention du token : <?=print_r($curl_exec)?>
							
						</div>
					<?
					}

				}
				?>
		
			</li>

            <li>
                Exploitation du token : 
				<input type="text" value="<?=@$access_token?>" class="w100" readonly>
            </li>

		</ol>
	</article>

</section>

<!-- Instagram -->
<style>
	#instagram-header {
		margin-top: -50px;
	    position: absolute;
	    z-index: 1;
	}
	#instagram-logo {
		border-radius: 100%;
		width: 67px;
		height: 67px;
		transition: all .3s;
	}
		#instagram-header:hover #instagram-logo { opacity: .6; }

	#instagram {
		margin: 0;
		padding: 0;
		display: grid;
		display: -ms-grid;
		grid-template-columns : repeat(6, 230px);
		-ms-grid-columns: (230px)[6];
	}
		#instagram li {
			float: left;
			padding: 1rem;
			max-width: 230px;
			max-height: 230px;
			overflow: hidden;
		}
			#instagram li:hover img { opacity: .6; }
			#instagram img {
				height: 230px;
				transition: all .3s;
			}
</style>

<section class="w80 mod center mbl pbm">

	<ul id="instagram" class="unstyled"></ul>

</section>

<script>
	$(function()
	{
		// Suivre la procédure a l'écran une fois la template attribuer à une page
		var url = 'https://graph.instagram.com/me/media';
		var token = '<?=$access_token?>';
		var fields = 'id,username,permalink,media_url,caption'; //cf https://developers.facebook.com/docs/instagram-basic-display-api/reference/media
		var num = 6;

		if(token == '' || typeof token == 'undefined') 
		{
			// Si pas de token => la notice
			$("ul#instagram").append("<li class='mtm'>Token non défini</li>");

		}
		else {	
			$.ajax({
				url: "https://graph.instagram.com/me/media",
				dataType: 'jsonp',
				type: 'GET',
				data: {fields: fields, access_token: token},
				success: function(data){
					
					console.log(data);

					for(cle = 0; cle < num; cle++)
					{
						$("ul#instagram").append('<li class="animation"><a href="'+data.data[cle].permalink+'" target="_blank"><img src="'+data.data[cle].media_url+'" data-lazy="'+data.data[cle].media_url+'" alt="'+data.data[cle].caption+'"></a></li>');
					}

					// Pour bien prendre en compte les images en lazyload injecté fraichement dans la dom
					$animation = $(".animation, [data-lazy]");
				},
				error: function(data){ console.log(data); }
			});
		}
	});
</script>
