/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// test composable
	import { useFile } from '@global/composables/useFile'



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	TYPE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('get file type', () => {

		// arrange
		const data = [
			{input: 'test.jpg', output: 'image'},
			{input: 'path/test.jpg?id=test', output: 'image'},
			{input: 'https://hello-nasty.com/path/test.jpg?id=test', output: 'image'},
			{input: 'TEST.JPG', output: 'image'},
			{input: 'double.extension.jpg', output: 'image'},

			{input: 'test.png', output: 'image'},
			{input: 'test.jpeg', output: 'image'},
			{input: 'test.gif', output: 'image'},
			{input: 'test.svg', output: 'image'},
			{input: 'test.webp', output: 'image'},

			{input: 'test.mp4', output: 'media'},
			{input: 'test.mp3', output: 'media'},

			{input: 'test.pdf', output: 'doc'},
			{input: 'test.txt', output: 'doc'},
			{input: 'test.doc', output: 'doc'},
			{input: 'test.vtt', output: 'doc'},

			{input: 'test.html', output: 'code'},
			{input: 'test.exe', output: 'file'},

			{input: 'noextension', output: 'file'},
			{input: null, output: null},
			{input: '', output: null},
			{input: {basename:'test.jpg'}, output: 'image'},
		];

		// act
		const { getFileType } = useFile();

		// assert
		data.forEach(({input, output}) => {
			expect(getFileType(input), "input: "+input).toBe(output);
		});
	});



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	SIZE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('format file size', () => {

		// arrange
		const data = [
			{input: 1024, output: '1 KB'},
			{input: 1024*1024*10, output: '10 MB'},
			{input: 0, output: '0 KB'},
			{input: -1024, output: '0 KB'},
			{input: 1024.5, output: '1 KB'},
		];

		// act
		const { formatFileSize } = useFile();

		// assert
		data.forEach(({input, output}) => {
			expect(formatFileSize(input), "input: "+input).toBe(output);
		});
	});


