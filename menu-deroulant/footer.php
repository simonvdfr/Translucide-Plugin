<?if(!$GLOBALS['domain']) exit;?>

<script>
$(function()
{
	/******* Menu en mode mobile *******/	
	if(ismobile()) {
		var current = null;
		$("header nav .l1").on("click", function(event)// click touchstart
		{
			if($(this).next("ul").length && current != $(this).attr("href")) {
				// Block le lien
				event.preventDefault();

				// Affiche le ul correspondant
				$("header nav ul").removeClass("hover");
				$(this).next("ul").addClass("hover");
			}
			
			current = $(this).attr("href");
		});
	}

	// Mode admin
	edit.push(function() {
		// Supprime l'action de click sur le lien
		$(".link a").on("click", function(event) { event.preventDefault(); });


		// EDITION NAVIGATION EN MENU DÉROULANT
		// https://github.com/ilikenwf/nestedSortable
		// Désactive le menu sortable classique du cms // disable
		$("header nav ul:first").sortable("destroy");

		// Charge nestedSortable
		draggable = false;
		$.ajax({
	        url: path+"theme/"+theme+"/nestedSortable.min.js",
	        dataType: 'script',
	        cache: true,
			success: function()
			{ 	
			    $("#add-nav .zone").on("click", function() 
			    {
			    	if(!draggable)
			    	{
				    	draggable = true;

				    	// Affichage des menus déroulant au clic sur l'edition de menu
				    	//$("header nav > ul > li ul").css("opacity", ($("header nav > ul > li ul").css("opacity") != 1?'1':'0'));
				    	//$("header nav > ul").removeClass("grid");
				    	//$("header nav > ul li").css({"display":"block", "float":"none"});
				    	//$("header nav > ul > li ul").css("position","relative");


				    	// Zone pour supprimer les menu imbriqué
						$("#add-nav").prepend("<ul class='del-zone'></ul>");
						
						// Rend le menu triable en menu déroulant et supprimable
						$("header nav ul:first, .del-zone").nestedSortable({
							connectWith: '.del-zone',// Zone de suppression
							listType: 'ul',
							handle: 'div',
							items: 'li',
							placeholder: 'placeholder',
							maxLevels: 2,            
							opacity: .5,
							//protectRoot: true,
							change: function(){
								tosave();

								$("#add-nav").removeClass("del");
								$("#add-nav .zone i").removeClass("fa-trash").addClass("fa-plus");

								// Si hover la zone de suppression on highlight
								//if($(".del-zone li").hasClass("ui-sortable-placeholder")) $(".layer-tags .fa-trash").addClass("hover");
								//else $(".layer-tags .fa-trash").removeClass("hover");
							},
							sort: function(){
								$("#add-nav").addClass("del");
								$("#add-nav .zone i").removeClass("fa-plus").addClass("fa-trash");
							},
							revert: function(){
								$("#add-nav").removeClass("del");
								$("#add-nav .zone i").removeClass("fa-trash").addClass("fa-plus");
							},
							relocate: function(){
								$("#add-nav").removeClass("del");
								$("#add-nav .zone i").removeClass("fa-trash").addClass("fa-plus");
							}
						});

						// Ajoute la croix de suppression
						$("nav > ul li").append("<i onclick='$(this).parent().remove()' class='fa fa-cancel absolute red pointer' style='top: -5px; right: -5px; z-index: 1;' title='"+ __("Remove") +"'></i>");
					}
					else 
					{
						draggable = false;

						// Supprime la croix de suppression
						$("nav .fa-cancel").remove();

						// Supprime le drag&drop
						$("header nav ul:first, .del-zone").nestedSortable("destroy");
					}

			    });			

			},
	        //async: true
	    });	

	});

});
</script>
