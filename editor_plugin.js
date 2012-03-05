
(function() {
	tinymce.create('tinymce.plugins.shopsite', {
		init : function(ed, url) {
			var disabled = true;

			ed.addCommand('open_shopsite', function() {
				ed.windowManager.open({
          url: path + 
            "/shopsite.php?ss_action=insert",
					width : 600,
					height : 400,
					title : "Loading... please wait.",
          inline: true
				}, {
					plugin_url : url // Plugin absolute URL
				});
			});

			// Register example button
			ed.addButton('shopsite', {
				title : 'Insert a ShopSite product',
				cmd : 'open_shopsite',
        image : url+'/ss-20.png'
			});

			ed.onNodeChange.add(function(ed, cm, n, co) {
				disabled = co && n.nodeName != 'A';
			});
      


		},

		getInfo : function() {
			return {
				longname : 'ShopSite Wordpress plugin',
				author : 'ShopSite',
				authorurl : 'http://shopsite.com',
				infourl : '',
				version : "1.0"
			};
		}
	});


	tinymce.PluginManager.add('shopsite', tinymce.plugins.shopsite);
})();
