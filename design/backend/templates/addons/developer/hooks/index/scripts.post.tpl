<script type="text/javascript">
	(function(_, $) {
		$.extend(_, {
			developer: {
				runtime: {
					mode: '{$runtime.mode}',
					controller: '{$runtime.controller}'
				},
				platform: {
					versionParsed: {$cscVersion|json_encode nofilter}
				}
			}
		});
	}(Tygh, Tygh.$));
</script>

{script src="js/addons/developer/backend.js"}