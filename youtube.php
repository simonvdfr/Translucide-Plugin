<style>
	#youtube a:after {
		content: '\e827';
		font-family: FontAwesome;
		font-size: 5rem;
		position: absolute;
		left: 50%;
		top: 50%;
		transform: translate(-50%, -50%);
		color: white;
		opacity: .8;
	}
	#youtube { transition: all .4s; }
	#youtube:hover { opacity: .8; }
</style>
<?
// Url de la vidéo
input('url-youtube', array("type" => "hidden", "placeholder" => "Adresse de partage youtube"));

// Extraction de l'id de la vidéo
if(@$content['url-youtube']) 
	preg_match("#(?<=v=)[a-zA-Z0-9-]+(?=&)|(?<=v\/)[^&\n]+|(?<=v=)[^&\n]+|(?<=youtu.be/)[^&\n]+#", $content['url-youtube'], $video);

if(@$video[0]) $video = $video[0]; else $video = null;
?>

<script>
	// https://developers.google.com/youtube/player_parameters?csw=1#Deprecated_Parameters
	// https://developers.google.com/youtube/youtube_player_demo
	function youtube() {
		$("#youtube").html('<iframe width="540" height="304" src="https://www.youtube.com/embed/<?=$video?>?controls=0&rel=0&autoplay=1" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>');
	}
</script>

<div id="youtube" class="relative"><a href="javascript:youtube();void(0);"><img src="https://img.youtube.com/vi/<?=$video?>/maxresdefault.jpg" width="540"></a></div>

