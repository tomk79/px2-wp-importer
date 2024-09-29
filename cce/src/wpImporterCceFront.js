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
			px2style.loading();

			const fileInfo = {
				'ext': $preview.attr('data-extension'),
				'mime_type': $preview.attr('data-mime-type'),
				'size': $preview.attr('data-size'),
			};
			const fullBase64 = $preview.attr('data-base64');

			const base64chunks = [];
			let indexNumber = 0;
			const chunkSize = 2 * 1000 * 1000;
			while (fullBase64.length > chunkSize*indexNumber) {
				base64chunks.push(fullBase64.substring(chunkSize*indexNumber, chunkSize*(indexNumber+1)));
				indexNumber++;
			}

			fileInfo.chunkCount = base64chunks.length;

			let modal;
			const $body = $(`<div>
				<div class="wp-importer__progress"><div class="wp-importer__progress-bar"></div></div>
			</div>`);
			const $progressBar = $body.find('.wp-importer__progress-bar');

			it79.fnc({}, [
				function(it){
					px2style.modal({
						body: $body,
						buttons: [],
					}, function(_modal){
						modal = _modal;
						modal.closable(false);
						it.next();
					});
					return;
				},
				function(it){
					cceAgent.gpi(
						{
							"command": 'upload_init',
							"fileInfo": fileInfo,
						},
						function(res){
							it.next();
						});
					return;
				},
				function(it){
					it79.ary(
						base64chunks,
						function(it2, chunk, num){
							num = Number(num);
							cceAgent.gpi(
								{
									"command": 'upload_chunk',
									"num": num,
									"chunk": chunk,
								},
								function(res){
									$progressBar.width(`${((num+1)/fileInfo.chunkCount)*100}%`);
									it2.next();
								});
							return;
						},
						function(){
							it.next();
							return;
						},
					);
					return;
				},
				function(it){
					cceAgent.gpi(
						{
							"command": 'upload_finalize',
						},
						function(res){
							it.next();
						});
					return;
				},
				function(it){
					cceAgent.gpi(
						{
							"command": 'import',
						},
						function(res){
							it.next();
						});
					return;
				},
				function(it){
					setTimeout(function(){
						it.next();
					} , 3000);
				},
				function(){
					modal.close();
					px2style.closeLoading();
				},
			]);

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