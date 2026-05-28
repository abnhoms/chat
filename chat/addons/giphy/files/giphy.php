<?php if(boomAllow($addons['addons_access'])){ ?>
<script data-cfasync="false">
var giphyKey = '<?php echo $addons['custom1']; ?>';
var giphyLimit = 0;
var giphyGifMax = <?php echo $addons['custom2'] ?>;
var giphyStickerMax = <?php echo $addons['custom3'] ?>;
var giphyType = 'gifs';
var giphyBox = 'giphy_gifs';
var giphyTemplate = '';
var giphyMode = 1;
var giphyTarget = 0;

getGiphy = function(md){
	giphyMode = md;
	if(md == 2){
		giphyTarget = currentPrivate;
	}
	else {
		giphyTarget = 0;
	}
	if(giphyTemplate == ''){
		$.post('addons/giphy/system/giphy_template.php', {
			}, function(response) {
				showEmptyModal(response, 360);
				resetGiphy();
				giphySearch(1);
				giphyTemplate = response;
		});
	}
	else {
		showEmptyModal(giphyTemplate, 360);
		resetGiphy();
		giphySearch(1);
	}
}
giphyItemTemplate = function(i, o, c, u, t){
	var giphyRes = '';
	if(t == 'stickers'){
		giphyRes = 2;
	}
	return '<div onclick="sendGiphy(\''+i+'\', \''+o+'\', \''+c+'\');" class="giphy_res_box'+giphyRes+'"><img src="'+u+'" autoplay loop></div>';
}

startGiphySearch = function(event, item){
	var giphyContent = $(item).val();
	if(event.keyCode == 13 && event.shiftKey == 0){
		if (/^\s+$/.test(giphyContent) || giphyContent == ''){
			return false;
		}
		else {
			giphySearch(0);
		}
	}
}
resetGiphy = function(){
	giphyType = 'gifs';
	giphyLimit = giphyGifMax;
	giphyBox = 'giphy_gifs';
	$('#'+giphyBox).show();
}
sendGiphyMain = function(gid, ori, ch){
	$.ajax({
		url: "addons/giphy/system/giphy_main.php",
		type: "post",
		cache: false,
		dataType: 'json',
		data: { 
			origin: ori,
			chat: ch,
			id: gid,
		},
		success: function(response){
			if(response.code == 1){
				appendSelfChatMessage(response.logs);
			}
			else {
				callError(system.error);
			}
		},
		error: function(){
			callError(system.error);
		}
	});
}
sendGiphyPrivate = function(gid, ori, ch){
	$.ajax({
		url: "addons/giphy/system/giphy_private.php",
		type: "post",
		cache: false,
		dataType: 'json',
		data: { 
			origin: ori,
			chat: ch,
			target: giphyTarget,
			id: gid,
		},
		success: function(response){
			if(response.code == 1){
				appendSelfPrivateMessage(response.logs);
			}
			else if(response.code == 99){
				appendCannotPrivate();
			}
			else {
				callError(system.error);
			}
		},
		error: function(){
			callError(system.error);
		}
	});
}	
sendGiphy = function(gid, ori, ch){
	hideModal();
	if(giphyMode == 1){
		sendGiphyMain(gid, ori, ch);
	}
	if(giphyMode == 2){
		sendGiphyPrivate(gid, ori, ch);
	}	
}
giphySelect = function(type){
	if(type == 2){
		giphyType = 'stickers';
		giphyBox = 'giphy_stickers';
		giphyLimit = giphyStickerMax;
		if($('#'+giphyBox).is(':empty')){
			giphySearch(2);
		}
	}
	else {
		giphyType = 'gifs';
		giphyBox = 'giphy_gifs';
		giphyLimit = giphyGifMax;
		if($('#'+giphyBox).is(':empty')){
			giphySearch(1);
		}
	}
}
giphySet = function(type){
	$('.giphy_results').hide();
	$('#'+type).show();	
}
giphySearch = function(type){
	var giphyData = {};
	var giphyUrl = '';
	if(giphyKey == ''){
		return false;
	}
	else {
		if(type == 1){
			giphyData = {api_key: giphyKey, limit: 8};
			giphyUrl = 'https://api.giphy.com/v1/gifs/trending';
		}
		else if(type == 2){
			giphyData = {api_key: giphyKey, limit: 16};
			giphyUrl = 'https://api.giphy.com/v1/stickers/trending';
		}
		else {
			giphyData = {q: $('#find_giphy').val(),api_key: giphyKey, limit: giphyLimit};
			giphyUrl = 'https://api.giphy.com/v1/'+giphyType+'/search?';
			$('#find_giphy').val('');
		}
		$.ajax({
		  url: giphyUrl,
		  type: 'GET',
		  dataType: 'json',
		  data: giphyData,
		})
		.done(function(data) {
		  if (data.meta.status == 200 && data.data.length > 0) {
			$('#'+giphyBox).html('');
			$('#'+giphyBox).scrollTop(0);
			for (var i = 0; i < data.data.length; i++) {
				var gifId = data.data[i].id;
				var gifOrigin = data.data[i].images.original.url;
				var gifHeight = data.data[i].images.fixed_height_small.url;
				var gifTumb = data.data[i].images.fixed_width_small.url;
				if(gifTumb == ''){
					gifTumb = data.data[i].images.fixed_width.url;
					gifHeight = data.data[i].images.fixed_width.url;
				}
				$('#'+giphyBox).append(giphyItemTemplate(gifId, gifOrigin, gifHeight, gifTumb, giphyType));
			}
		  }
		  else{
			$('#'+giphyBox).html(noDataTemplate());
		  }
		})
		.fail(function() {
			return false;
		})
	}
}

$(document).ready(function(){
	appInputMenu('addons/giphy/files/giphy.svg', 'getGiphy(1);');
	appPrivInputMenu('addons/giphy/files/giphy.svg', 'getGiphy(2);');
	boomAddCss('addons/giphy/files/giphy.css');
});

</script>
<?php } ?>