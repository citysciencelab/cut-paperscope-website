/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	const path 			= require('path');
	const fs 			= require('fs-extra');
	const Helper 		= require('../helper.js');
	const { optimize } 	= require('svgo');
	const SVGSpriter 	= require('svg-sprite');



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	MAIN
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	execute();



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	EXECUTE SCRIPT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	function execute(input) {

		createSprite('app');
		createSprite('backend');
		createSpriteIcons('app');
		createSpriteIcons('backend');

		convertInlineSvgs('app');
		convertInlineSvgs('backend');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	SPRITE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	function createSprite(environment) {

		var config = {
			dest: __dirname + '/../../public/svg/'+environment+'/',
			mode: {symbol: true},
			shape: {
				transform: [{
					svgo: {
						plugins: [{
							name: 'preset-default',
							params: {
								overrides: {
									inlineStyles: { onlyMatchedOnce: false },
									removeViewBox: false,
								}
							}
						}]
					}
				}]
			}
		};

		// get all svg files
		var files = getSvgFiles(__dirname + '/../../resources/svg/'+environment+'/sprite/');

		// add files to spriter
		var spriter = new SVGSpriter(config);
		files.forEach(i=> spriter.add(i, null, fs.readFileSync(i, 'utf-8')));

		spriter.compile((error, result) => {
			for (const mode in result) {
				for (const resource in result[mode]) {
					fs.mkdirSync(config.dest, { recursive: true });
					fs.writeFileSync(config.dest+'sprite.svg', result[mode][resource].contents);
				}
			}
		});

		console.log('Sprite created: '+environment+'/sprite.svg');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	SPRITE ICONS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	function createSpriteIcons(environment) {

		var config = {
			dest: __dirname + '/../../public/svg/'+environment+'/',
			mode: {symbol: true},
			shape: {
				transform: [{
					svgo: {
						plugins: [
							{
								name: 'preset-default',
								params: {
									overrides: { removeViewBox: false, }
								}
							},
							{
								name: "removeStyleElement",
								active: true
							},
							{
								name: "removeAttrs",
								params: { attrs: 'style' }
							}
						]
					}
				}]
			}
		};

		// get all svg files
		var files = getSvgFiles(__dirname + '/../../resources/svg/'+environment+'/icons/');

		// add files to spriter
		var spriter = new SVGSpriter(config);
		files.forEach(i=> spriter.add(i, null, fs.readFileSync(i, 'utf-8')));

		spriter.compile((error, result) => {
			for (const mode in result) {
				for (const resource in result[mode]) {
					fs.mkdirSync(config.dest, { recursive: true });
					fs.writeFileSync(config.dest+'sprite-icons.svg', result[mode][resource].contents);
				}
			}
		});

		console.log('Sprite created: '+environment+'/sprite-icons.svg');
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INLINE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	function convertInlineSvgs(environment) {

		// get all svg files
		var files = fs.readdirSync(__dirname + '/../../public/svg/'+environment+'/');

		files.forEach(i=> {

			// skip if not svg file
			if(i.indexOf('.svg')==-1) { return; }

			// read file
			var svg = fs.readFileSync(__dirname + '/../../public/svg/'+environment+'/'+i, 'utf8');

			// replace illustrator classes
			svg = replaceIllustratorClasses(svg);

			// save file
			fs.outputFile( __dirname + '/../../public/svg/'+environment+'/'+i, svg, err => {

				if(err) { console.log('Unable to write controller file: ',err);	}
				else { console.log('SVG file converted: '+environment+'/'+i); }
			});
		});
	}


	function replaceIllustratorClasses(svg) {

		// replace illustrator classes
		for(var i=0; i<10; i++) {

			var newName = makeHash(16);

			var re = new RegExp("\\.st"+i+"\s*\\{","gm")
			svg = svg.replace(re, '.'+newName+'{');

			var re = new RegExp("class=\"st"+i+"\"","gm")
			svg = svg.replace(re, "class=\""+newName+"\"");
		}

		return svg;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	FILES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	function getSvgFiles(folder) {

		folder = Helper.validatePath(folder);

		var svgs = [];
		var files=fs.readdirSync(folder);

		for(var i=0; i<files.length; i++){

			var filename = path.join(folder,files[i]);
			var stat = fs.lstatSync(filename);

			// read folder recursivly
			if (stat.isDirectory()){
				svgs = svgs.concat(getSvgFiles(filename));
			}

			else if (filename.indexOf('.svg')>=0 && filename.indexOf('sprite.svg')==-1 && filename.indexOf('sprite-icons.svg')==-1) {
				svgs.push(filename);
			};
		};

		return svgs;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	SVG CONVERT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	function makeHash(length) {

		var result           = '';
		var characters       = 'abcdefghijklmnopqrstuvwxyz0123456789';
		var charactersLength = characters.length;

		for ( var i = 0; i < length; i++ ) {
	 		result += characters.charAt(Math.floor(Math.random() * charactersLength));
  		}

		// replace first char if digit
		if(/^\d/.test(result)) { result = 'a' + result.substring(1); }

		return result;
	}


