

	exports.validatePath = (path) => {

		return path.endsWith('/') ? path : (path ? path+'/': '');
	};


	exports.capitalizeFirstLetter = (target) => {

		return target[0].toUpperCase() + target.slice(1);
	};


	exports.convertCamelCaseToDash = (target) => {

		target = target[0].toLowerCase() + target.slice(1);
		return target.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase();
	};


	exports.onInquirerError = (error) => {

		if(error.isTtyError) {
      		console.error("Prompt couldn't be rendered in the current environment");
    	}
		else {
      		console.error("Error: ",error);
    	}
	};
