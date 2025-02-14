/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// vue



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	COMPOSABLE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


export const useDate = () => {


	/////////////////////////////////
	// TIMESTAMP
	/////////////////////////////////

	/**
	 * Convert a string in the format "j.n.Y H:i" to a timestamp
	 */

	function dateToTimestamp(dateTime?: string): number {

		if(!dateTime) { return 0; }

		const parts = dateTime.split(' ');
		const date = parts[0].split('.').map(p => parseInt(p));
		const time = parts.length > 1 ? parts[1].split(':').map(p => parseInt(p)) : [0, 0];

		const year = date[2];
		const month = date[1] - 1;
		const day = date[0];
		const hour = time[0];
		const minute = time[1];

		return new Date(year, month, day, hour, minute).getTime();
	}


	/**
	 * Convert a timestamp to a string in the format "j.n.Y H:i"
	 */

	function timestampToDate(timestamp?: string | number): string {

		if(timestamp == null) { return ''; }

		timestamp = parseInt(timestamp.toString());

		// convert php timestamp
		if(timestamp.toString().length < 13) timestamp *= 1000;

		const date = new Date(timestamp);
		const year = date.getFullYear();
		const month = date.getMonth() + 1;
		const day = date.getDate();
		const hour = String(date.getHours()).padStart(2, '0');
		const minutes = String(date.getMinutes()).padStart(2, '0');

		return `${day}.${month}.${year} ${hour}:${minutes}`;
	}


	/////////////////////////////////
	// DATE
	/////////////////////////////////

	/**
	 * Format a date object to a string in the format "j.n.Y"
	 */

	function formatDate(date?: Date): string {

		return timestampToDate(date?.getTime());
	}


	/////////////////////////////////
	// TIME
	/////////////////////////////////

	/**
	 * Format a time string (from a form input) in the format "H:i"
	 */

	function formatTime(currentValue?: string): string {

		if(!currentValue) { return '00:00'; }

		// Remove all characters except numbers, colons
		var val = currentValue.replace(/[^\d:]/g, '');
		val = val.replace('::', ':');

		// add colon if missing
		if(val.length == 2 && !val.includes(':')) { val += ':'; }
		else if(val.length > 2 && !val.includes(':')) { val = val.substring(0, 2) + ':' + val.substring(2); }

		var parts = val.split(':');
		var hours = parseInt(parts[0]);
		var minutes = parts.length > 1 ? parseInt(parts[1]) : 0;

		// validate hours
		if(Number.isNaN(hours)) hours = 0;
		if(hours < 0 || hours > 23) hours = 23;

		// validate minutes
		if(Number.isNaN(minutes)) minutes = 0;
		if(minutes > 59) minutes = 59;

		return String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
	}


	/**
	 * Convert seconds to a time string in the format "H:i:ss"
	 */

	function secondsToTime(seconds?: number): string {

		if(seconds == null) return '00:00';

		var hours = Math.floor(seconds / 3600);
		var minutes = Math.floor((seconds - (hours * 3600)) / 60);
		var seconds = seconds - (hours * 3600) - (minutes * 60);

		// show hours only if needed
		if(hours == 0) {
			return String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
		}

		return String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
	}


	/////////////////////////////////
	// EXPORT
	/////////////////////////////////

	return {
		dateToTimestamp, timestampToDate,
		formatDate,
		formatTime, secondsToTime
	};



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */



};
