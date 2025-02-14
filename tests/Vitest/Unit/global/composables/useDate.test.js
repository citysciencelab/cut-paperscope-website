/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// test composable
	import { useDate } from '@global/composables/useDate'



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	TIMESTAMP
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('formatted date to timestamp', () => {

		// arrange
		const data = [
			{input: '24.9.2024 21:46', output: 1727207160000},
			{input: '1.1.1970 01:00', output: 0},
			{input: '', output: 0},
			{input: null, output: 0},
		];

		// act
		const {dateToTimestamp} = useDate();

		// assert
		data.forEach(({input, output}) => {
			expect(dateToTimestamp(input), "input: "+input).toBe(output);
		});
	});



	test('timestamp to formatted date', () => {

		// arrange
		const data = [
			{input: 1727207198, output: '24.9.2024 21:46'},			// php timestamp
			{input: 1727207198 * 1000, output: '24.9.2024 21:46'},	// js timestamp
			{input: '1727207198000', output: '24.9.2024 21:46'},
			{input: 0, output: '1.1.1970 01:00'},
			{input: null, output: ''},
		];

		// act
		const {timestampToDate} = useDate();

		// assert
		data.forEach(({input, output}) => {
			expect(timestampToDate(input), "input: "+input).toBe(output);
		});
	});


/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	DATE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('date object to formatted date', () => {

		// arrange
		const data = [
			{input: new Date('2024', '8', '24', '21', '46'), output: '24.9.2024 21:46'},
			{input: new Date('1970', '0', '1', '1', '0'), output: '1.1.1970 01:00'},
			{input: null, output: ''},
		];

		// act
		const {formatDate} = useDate();

		// assert
		data.forEach(({input, output}) => {
			expect(formatDate(input), "input: "+input).toBe(output);
		});
	});



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	TIME
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('form input to formatted time', () => {

		// arrange
		const data = [
			{input: '1:', output: '01:00'},
			{input: '1:2', output: '01:02'},
			{input: '1:23', output: '01:23'},
			{input: '12:', output: '12:00'},
			{input: '14', output: '14:00'},
			{input: '145', output: '14:05'},
			{input: '3', output: '03:00'},
			{input: '3:', output: '03:00'},
			{input: '3:4', output: '03:04'},
			{input: '18:5', output: '18:05'},
			{input: '18:6', output: '18:06'},
			{input: '18:06', output: '18:06'},
			{input: '18:8', output: '18:08'},
			{input: '99:99', output: '23:59'},
			{input: null, output: '00:00'},
			{input: '', output: '00:00'},
			{input: 'XX:59', output: '00:59'},
			{input: '12::00', output: '12:00'},
			{input: '1200', output: '12:00'},
		];

		// act
		const {formatTime} = useDate();

		// assert
		data.forEach(({input, output}) => {
			expect(formatTime(input), "input: "+input).toBe(output);
		});
	});


	test('seconds to formatted time', () => {

		// arrange
		const data = [
			{input: 1, output: '00:01'},
			{input: 60, output: '01:00'},
			{input: 61, output: '01:01'},
			{input: 3600, output: '01:00:00'},
			{input: 3601, output: '01:00:01'},
			{input: null, output: '00:00'},
		];

		// act
		const {secondsToTime} = useDate();

		// assert
		data.forEach(({input, output}) => {
			expect(secondsToTime(input), "input: "+input).toBe(output);
		});
	});



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


