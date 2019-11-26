<?
case "facebook-photos":// Liste des images que l'on a sur facebook
			
	//@todo: check si access token facebook disponible

	login('medium', 'edit-page');// Vérifie que l'on est admin

	// https://graph.facebook.com/me/albums
	// https://graph.facebook.com/id-album/photos?fields=source,name,id,link&access_token=
	// https://graph.facebook.com/id-album/picture > cover

	// url ultime qui renvoi tous les albums et les photos : https://graph.facebook.com/me/albums?fields=photos&access_token=CAAJ9CCDJ1kkBAGYMbiqGDHXCuYIEy4SMIWQ6GIJ4tMIlfuhtoLdjEOL323YEtOxpI95AshuDdxTxHFYXy4jPg8QYUgo5GJedRpnZBqzHSvbvm4nZBMsRlTs90Up4uZCGRha560lgsiH0IaysKx1VsDQLpm4dVrAQczMVYKvfWsVfCcZBuuQA
	
	// url ultime 2 :		https://graph.facebook.com/me/?fields=albums.fields%28id,name,cover_photo,photos.fields%28name,picture,source%29%29&access_token=CAAJ9CCDJ1kkBAGYMbiqGDHXCuYIEy4SMIWQ6GIJ4tMIlfuhtoLdjEOL323YEtOxpI95AshuDdxTxHFYXy4jPg8QYUgo5GJedRpnZBqzHSvbvm4nZBMsRlTs90Up4uZCGRha560lgsiH0IaysKx1VsDQLpm4dVrAQczMVYKvfWsVfCcZBuuQA

	// /me/albums  /me/photos/uploaded
	if($_SESSION['access_token_external'] and $_SESSION['login_api'] == "facebook")
	$response = json_decode(curl("https://graph.facebook.com/me/albums?&access_token=".$tab_token_response['access_token']), true);

	echo "response<br>"; highlight_string(print_r($response, true));

	//@todo: prévoir une navigation par page pour les albums et les photos
	//@todo: si album vide = on ne l'affiche pas

break;
?>