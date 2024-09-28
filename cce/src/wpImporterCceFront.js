window.wpImporterCceFront = function(cceAgent){
	const $ = require('jquery');
	const it79 = require('iterate79');
	const $elm = $(cceAgent.elm());
	$elm.html(bindTwig(
		require('-!text-loader!./includes/templates/main.twig'),
		{}
	));
	const $preview = $elm.find('.wp-importer__preview');

	$elm.find('input[type=file][name=wp-importer__import-file]')
		.on('change', function(event){
			var fileInfo = event.target.files[0];
			var realpathSelected = $(this).val();

			if( realpathSelected ){
				applyFile(fileInfo);
			}
		});

	$elm.find('form')
		.on('submit', function(event){
			event.preventDefault();

			const fileInfo = {
				'file': $preview.attr('data-base64'),
				'ext': $preview.attr('data-extension'),
				'mime_type': $preview.attr('data-mime-type'),
				'size': $preview.attr('data-size'),
			};

			px2style.loading();

			cceAgent.gpi(
				{
					"command": 'upload',
					"fileInfo": fileInfo,
				},
				function(res){
					console.log('---- res:', res);
					px2style.closeLoading();
				});
		});

	/**
	 * fileAPIからファイルを取り出して反映する
	 */
	function applyFile(fileInfo){
		function readSelectedLocalFile(fileInfo, callback){
			var reader = new FileReader();
			reader.onload = function(evt) {
				callback( evt.target.result );
			}
			reader.readAsDataURL(fileInfo);
		}

		// mod.filename
		readSelectedLocalFile(fileInfo, function(dataUri){
			it79.fnc({}, [
				function(it){
					it.next();
					return;
				},
				function(it){
					setPreview({
						'src': dataUri,
						'size': fileInfo.size,
						'ext': getExtension( fileInfo.name ),
						'mimeType': fileInfo.type,
						'base64': (function(dataUri){
							dataUri = dataUri.replace(new RegExp('^data\\:[^\\;]*\\;base64\\,'), '');
							return dataUri;
						})(dataUri),
					});
					it.next();
				},
			]);
		});
	}

	/**
	 * パスから拡張子を取り出して返す
	 */
	function getExtension(path){
		var ext = '';
		try {
			var ext = path.replace( new RegExp('^.*?\.([a-zA-Z0-9\_\-]+)$'), '$1' );
			ext = ext.toLowerCase();
		} catch (e) {
			ext = false;
		}
		return ext;
	}

	/**
	 * プレビューを更新する
	 */
	function setPreview(fileInfo){
		var fileSrc = fileInfo.src;
		var fileMimeType = fileInfo.mimeType;
		if( !fileInfo.src || !fileInfo.ext || !fileInfo.size){
			fileSrc = _imgDummy;
			fileMimeType = 'image/png';
		}

		$preview
			.attr({
				"data-size": fileInfo.size ,
				"data-extension": fileInfo.ext,
				"data-mime-type": fileMimeType ,
				"data-base64": fileInfo.base64,
				"data-is-updated": 'yes'
			})
			.text(fileMimeType)
		;
		return;
	}

	/**
	 * Twig テンプレートにデータをバインドする
	 */
	function bindTwig( tpl, data ){
		var rtn = '';
		var Twig, twig;
		try {
			Twig = require('twig'), // Twig module
			twig = Twig.twig;

			rtn = new twig({
				'data': tpl,
				'autoescape': true,
			}).render(data);
		} catch(e) {
			var errorMessage = 'TemplateEngine "Twig" Rendering ERROR.';
			console.error( errorMessage );
			rtn = errorMessage;
		}
		return rtn;
	}
}