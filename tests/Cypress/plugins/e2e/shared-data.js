
module.exports = {

    setSharedData({ key, value }) {

		const timestamp = new Date().getTime();

		if(!global.sharedData) { global.sharedData = {}; }
		global.sharedData[key] = {value, timestamp};

		return value;
    },

    getSharedData(key) {

		const timestamp = new Date().getTime();

		if(global.sharedData && global.sharedData[key] && global.sharedData[key].timestamp > timestamp - 1000*60) {
			return global.sharedData[key].value;
		}
		else {
			return null;
		}
	},

	removeSharedData(key) {

		return global.sharedData ? global.sharedData[key] : null;
    }
};
