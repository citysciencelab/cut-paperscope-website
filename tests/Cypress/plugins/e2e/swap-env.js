let fs = require('fs');

module.exports = {

    activateCypressEnvFile() {

        if (fs.existsSync('.env.testing')) {
            fs.renameSync('.env', '.env.backup');
            fs.renameSync('.env.testing', '.env');
        }

        return null;
    },

    activateLocalEnvFile() {

		if (fs.existsSync('.env.backup')) {
            fs.renameSync('.env', '.env.testing');
            fs.renameSync('.env.backup', '.env');
        }

        return null;
    }
};
