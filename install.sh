#!/bin/bash
clear



#///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
#/
#/	HELPER
#/
#///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	# Wrapper function for old bash (<4.0 on Mac) to support parameter "-i"
	function readinput() {

		local CLEAN_ARGS=""
		while [[ $# -gt 0 ]]; do
			local i="$1"
			case "$i" in
			"-i")
				if read -i "default" 2>/dev/null <<< "test"; then
				CLEAN_ARGS="$CLEAN_ARGS -i \"$2\""
				fi
				shift
				shift
				;;
			"-p")
				CLEAN_ARGS="$CLEAN_ARGS -p \"$2\""
				shift
				shift
				;;
			*)
				CLEAN_ARGS="$CLEAN_ARGS $1"
				shift
				;;
			esac
		done
		eval read $CLEAN_ARGS
	}


	function replaceEnv() {

		local envFile="$1"
		local envKey="$2"
		local envValue="$3"

		# escape "," char (used as sed separator)
		envValue="$(sed -E 's/\,/\\\,/g' <<<$envValue)"

		if [[ "$OSTYPE" == "darwin"* ]]; then
			sed -E -i '' "s,^$envKey=(\\\")?[^\\\"]*(\\\")?,$envKey=\1$envValue\2,g" $envFile
		else
			sed -E -i "s,^$envKey=(\\\")?[^\\\"]*(\\\")?,$envKey=\1$envValue\2,g" $envFile
		fi
	}


	function convertToBool() {

		local value="$1"

		# convert to lowercase
		value="$(tr '[:upper:]' '[:lower:]' <<<$value)"

		if [[ "$value" == "y" ]]; then
			value="true"
		else
			value="false"
		fi

		echo "$value"
	}


	function printBanner() {

		printf "\n\n\n"
		printf "##################################################################################\n"
		printf "=> $1\n"
		printf "##################################################################################\n\n"
	}


	function slugify() {

		local text="$1"

		# replace spaces with "-"
		text="$(sed -E 's/ /_/g' <<<$text)"

		# replace german umlauts
		text="$(sed -E 's/ä/ae/g' <<<$text)"
		text="$(sed -E 's/ö/oe/g' <<<$text)"
		text="$(sed -E 's/ü/ue/g' <<<$text)"
		text="$(sed -E 's/Ä/Ae/g' <<<$text)"
		text="$(sed -E 's/Ö/Oe/g' <<<$text)"
		text="$(sed -E 's/Ü/Ue/g' <<<$text)"
		text="$(sed -E 's/ß/ss/g' <<<$text)"

		# remove special characters
		text="$(sed -E 's/[^a-zA-Z0-9_]//g' <<<$text)"

		# convert to lowercase
		text="$(tr '[:upper:]' '[:lower:]' <<<$text)"

		echo "$text"
	}


	function replaceTextInFile() {

		local filePath="$1"
		local search="$2"
		local replace="$3"

		# add line break to replace
		search="$search\\r*\\n*"

		if [[ "$OSTYPE" == "darwin"* ]]; then
			#sed -i '' -E "s,$search,$replace,g" $filePath
			perl -i -pe "s,$search,$replace,g" $filePath
		else
			sed -i -E "s,$search,$replace,g" $filePath
		fi
	}


	function removeLineInFile() {

		local filePath="$1"
		local search="$2"

		# escape "(" and ")" char
		search="$(sed -E 's/\(/\\(/g' <<<$search)"
		search="$(sed -E 's/\)/\\)/g' <<<$search)"

		if [[ "$OSTYPE" == "darwin"* ]]; then
			#sed -i '' -E "/$search/d" $filePath
			perl -i -ne "print unless /$search/" $filePath
		else
			sed -i -E "/$search/d" $filePath
		fi
	}



#///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
#/
#/	REQUIREMENTS
#/
#///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	# colors
	RED='\033[0;31m'
	GREEN='\033[0;32m'
	NC='\033[0m' # no Color

	printBanner "REQUIREMENTS"

	# check if node is installed
	printf "=> check if node is installed: "
	if ! command -v node &>/dev/null; then
		printf "${RED}failed${NC}\n"
		exit
	else
		printf "${GREEN}succeeded${NC}\n"
	fi

	# check if node version is >= 20
	printf "=> check if node version is >= 20: "
	nodeVersion="$(node -v)"
	nodeVersion="${nodeVersion:1}"
	nodeVersion="${nodeVersion:0:2}"
	if [[ "$nodeVersion" -lt 20 ]]; then
		printf "${RED}failed${NC} (found v${nodeVersion}) \n"
		exit
	else
		printf "${GREEN}succeeded${NC}\n"
	fi

	# check if npm is installed
	printf "=> check if npm is installed: "
	if ! command -v npm &>/dev/null; then
		printf "${RED}failed${NC}\n"
		exit
	else
		printf "${GREEN}succeeded${NC}\n"
	fi

	# check if npm version is >= 7
	printf "=> check if npm version is >= 10: "
	npmVersion="$(npm -v)"
	npmVersion="${npmVersion:0:2}"
	if [[ "$npmVersion" -lt 10 ]]; then
		printf "${RED}failed${NC} (found v${npmVersion}) \n"
		exit
	else
		printf "${GREEN}succeeded${NC}\n"
	fi

	# check if composer is installed
	printf "=> check if composer is installed: "
	if ! command -v composer &>/dev/null; then
		printf "${RED}failed${NC}\n"
		exit
	else
		printf "${GREEN}succeeded${NC}\n"
	fi

	# check if php is installed
	printf "=> check if php is installed: "
	if ! command -v php &>/dev/null; then
		printf "${RED}failed${NC}\n"
		exit
	else
		printf "${GREEN}succeeded${NC}\n"
	fi

	# check if php version is >= 8.2
	printf "=> check if php version is >= 8.2: "
	phpVersion="$(php -v)"
	phpVersion="${phpVersion:4:3}"
	if [[ "$phpVersion" < 8.2 ]]; then
		printf "${RED}failed${NC} (found v${phpVersion}) \n"
		exit
	else
		printf "${GREEN}succeeded${NC}\n"
	fi



#///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
#/
#/	ENV SETTINGS
#/
#///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	printBanner ".ENV SETTINGS"

	# create local .env file
	cp .env.example .env

	# set APP_NAME
	readinput -p "Set APP_NAME:  " -i "Website Laravel" -e appName
	replaceEnv ".env" "APP_NAME" "$appName"

	# read APP_URL
	readinput -p "Set APP_URL (include 'public' on localhost):  " -i "http://localhost/client/project/public/" -e appUrlRaw

	# append trailing "/" to APP_URL
	[[ "${appUrlRaw}" != */ ]] && appUrlRaw="${appUrlRaw}/"

	# set APP_URL
	appUrl="$(sed -E 's/\//\\\//g' <<<$appUrlRaw)"
	replaceEnv ".env" "APP_URL" "$appUrl"

	# get domain from APP_URL
	appDomain="$(sed -E 's/^(http[s]?:\/\/)([a-z0-9:.-]{1,})(.*)/\2/' <<<$appUrlRaw)"
	appDomain="$(sed -E 's/\//\\\//g' <<<$appDomain)"

	# set SESSION_DOMAIN
	replaceEnv ".env" "SESSION_DOMAIN" "$appDomain"

	# set ROOT_PASSWORD
	readinput -p "Set password for ROOT user:  " -e rootPassword
	replaceEnv ".env" "ROOT_PASSWORD" "$rootPassword"

	# set database credentials
	readinput -p "Name for database:  " -e dbName
	replaceEnv ".env" "DB_DATABASE" "$dbName"
	readinput -p "Username for database:  " -e dbUser
	replaceEnv ".env" "DB_USERNAME" "$dbUser"
	readinput -p "Password for database:  " -e dbPassword
	replaceEnv ".env" "DB_PASSWORD" "$dbPassword"

	# set queue names
	queueSlug=$(slugify "${appName}_queue")
	replaceEnv ".env" "SQS_QUEUE" "$queueSlug"
	replaceEnv ".env" "REDIS_QUEUE" "$queueSlug"



#///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
#/
#/	PROJECT FEATURES
#/
#///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	printBanner "PROJECT FEATURES"

	##################################
	# FEATURE_BACKEND
	##################################

	readinput -p "Use feature BACKEND (y/n):  " -e featureBackend
	featureBackend="$(convertToBool "$featureBackend")"
	replaceEnv ".env" "FEATURE_BACKEND" "$featureBackend"

	if [[ "$featureBackend" == "false" ]]; then

		replaceTextInFile "vite.config.js" "\t'backend/backend':" "\t//'backend/backend':"
		replaceTextInFile "vite.config.js" "\t'css/backend':" "\t//'css/backend':"
		replaceTextInFile "vite.config.js" "\t'resources/js/backend/pages" "\t//'resources/js/backend/pages"
		replaceTextInFile "vite.config.js" "\t'resources/js/backend/components" "\t//'resources/js/backend/components"
		replaceTextInFile "vite.config.js" "\t'resources/js/backend/composables" "\t//'resources/js/backend/composables"

		replaceTextInFile ".gitlab-ci.yml" "FEATURE_BACKEND: \"true\"" "FEATURE_BACKEND: \"false\""
	fi


	##################################
	# FEATURE_BACKEND_RESET
	##################################

	featureBackendReset="false"

	if [[ "$featureBackend" == "true" ]]; then

		readinput -p "Use feature BACKEND_RESET (y/n):  " -e featureBackendReset
		featureBackendReset="$(convertToBool "$featureBackendReset")"
		replaceEnv ".env" "FEATURE_BACKEND_RESET" "$featureBackendReset"
	fi

	if [[ "$featureBackendReset" == "false" ]]; then

		removeLineInFile "resources/js/global/pages/auth/PageLogin.vue" "link('password.forgot')"
		replaceTextInFile "resources/js/global/routes/AuthRoutes.js" "const PageForgotPassword" "//const PageForgotPassword"
		replaceTextInFile "resources/js/global/routes/AuthRoutes.js" "const PageResetPassword" "//const PageResetPassword"
		replaceTextInFile "resources/js/global/routes/AuthRoutes.js" "{ path: 'password/forgot" "//{ path: 'password/forgot"
		replaceTextInFile "resources/js/global/routes/AuthRoutes.js" "{ path: 'password/reset" "//{ path: 'password/reset"

		replaceTextInFile ".gitlab-ci.yml" "FEATURE_BACKEND_RESET: \"true\"" "FEATURE_BACKEND_RESET: \"false\""
	fi


	##################################
	# FEATURE_APP_ACCOUNTS
	##################################

	featureAppAccounts="false"

	if [[ "$featureBackend" == "true" ]]; then

		readinput -p "Use feature APP_ACCOUNTS (y/n):  " -e featureAppAccounts
		featureAppAccounts="$(convertToBool "$featureAppAccounts")"
		replaceEnv ".env" "FEATURE_APP_ACCOUNTS" "$featureAppAccounts"
	fi

	if [[ "$featureAppAccounts" == "false" ]]; then

		removeLineInFile "resources/js/app/App.js" "components\/user\/"
		replaceTextInFile "resources/js/app/AppRouter.js" "\\timport AuthRoutes" "\\t\/\/import AuthRoutes"
		replaceTextInFile "resources/js/app/AppRouter.js" "\\timport UserRoutes" "\\t\/\/import UserRoutes"
		replaceTextInFile "resources/js/app/AppRouter.js" "\\troutes = routes.concat\\(AuthRoutes\\)" "\\t\/\/routes = routes.concat(AuthRoutes)"
		replaceTextInFile "resources/js/app/AppRouter.js" "\\troutes = routes.concat\\(UserRoutes\\)" "\\t\/\/routes = routes.concat(UserRoutes)"

		removeLineInFile "resources/sass/app/app.scss" "user"

		removeLineInFile "resources/js/app/components/header/HeaderNavi.vue" "<!-- AUTH -->"
		removeLineInFile "resources/js/app/components/header/HeaderNavi.vue" "<header-navi-item v-if=\"user"
		removeLineInFile "resources/js/app/components/header/HeaderNavi.vue" "<header-navi-item v-if=\"!user"
		removeLineInFile "resources/js/app/components/header/HeaderNavi.vue" "useUser"

		removeLineInFile "tests/PHPUnit/phpunit.xml" "<!-- APP ACCOUNTS TEST"
		removeLineInFile "tests/PHPUnit/phpunit.xml" "APP ACCOUNTS TEST -->"

		replaceTextInFile ".gitlab-ci.yml" "FEATURE_APP_ACCOUNTS: \"true\"" "FEATURE_APP_ACCOUNTS: \"false\""
	fi


	##################################
	# FEATURE_SHOP
	##################################

	featureShop="false"

	if [[ "$featureAppAccounts" == "true" ]]; then

		readinput -p "Use feature SHOP (y/n):  " -e featureShop
		featureShop="$(convertToBool "$featureShop")"
		replaceEnv ".env" "FEATURE_SHOP" "$featureShop"
	fi

	if [[ "$featureShop" == "false" ]]; then

		removeLineInFile "resources/js/app/App.js" "components\/shop\/"
		removeLineInFile "resources/js/app/components/header/HeaderNavi.vue" "to=\"shop\""
		removeLineInFile "resources/js/app/AppRouter.js" "ShopRoutes"

		removeLineInFile "resources/sass/app/app.scss" "shop"

		removeLineInFile "resources/js/backend/BackendRouter.js" "ShopRoutes"
		removeLineInFile "resources/js/backend/components/navi/BackendNavi.vue" "backend.product"

		removeLineInFile "tests/PHPUnit/phpunit.xml" "<!-- SHOP TEST"
		removeLineInFile "tests/PHPUnit/phpunit.xml" "SHOP TEST -->"

		replaceTextInFile ".gitlab-ci.yml" "FEATURE_SHOP: \"true\"" "FEATURE_SHOP: \"false\""
	fi


	##################################
	# FEATURE_MULTI_LANG
	##################################

	readinput -p "Use feature MULTI_LANG (y/n):  " -e featureMultiLang
	featureMultiLang="$(convertToBool "$featureMultiLang")"
	replaceEnv ".env" "FEATURE_MULTI_LANG" "$featureMultiLang"

	if [[ "$featureMultiLang" == "false" ]]; then

		removeLineInFile "resources/js/app/components/header/HeaderNavi.vue" "<language-select"

		removeLineInFile "database/migrations/base/model/0002_01_01_000000_create_pages_table.php" "translate("
		removeLineInFile "database/migrations/base/model/0002_01_01_000003_create_fragments_table.php" "translate("
		removeLineInFile "database/migrations/base/model/0002_01_01_000004_create_settings_table.php" "translate("
		removeLineInFile "database/migrations/base/model/0003_01_01_000002_create_products_table.php" "translate("

		replaceTextInFile "database/factories/App/PageFactoryData.json" "_de\":" "\":"
		removeLineInFile "database/factories/App/FragmentFactoryData.json" "content_en"
		replaceTextInFile "database/factories/App/FragmentFactoryData.json" "copy_de" "copy"
		replaceTextInFile "database/factories/Backend/SettingFactoryData.json" "_de\":" "\":"
		replaceTextInFile "database/factories/Shop/ProductFactoryData.json" "_de\":" "\":"

		replaceTextInFile "database/factories/Backend/SettingFactory.php" "content_de" "content"

		replaceTextInFile ".gitlab-ci.yml" "FEATURE_MULTI_LANG: \"true\"" "FEATURE_MULTI_LANG: \"false\""
	fi



#///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
#/
#/	TESTING
#/
#///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	##################################
	# write .env.testing from .env
	##################################

	cp .env .env.testing

	replaceEnv ".env.testing" "DB_CONNECTION" "testing"
	replaceEnv ".env.testing" "CACHE_STORE" "file"
	replaceEnv ".env.testing" "QUEUE_CONNECTION" "sync"
	replaceEnv ".env.testing" "FILESYSTEM_DISK" "testing"
	replaceEnv ".env.testing" "BROADCAST_CONNECTION" "null"

	if [[ "$featureShop" == "true" ]]; then

		replaceEnv ".env.testing" "STRIPE_KEY" "pk_test_0"
		replaceEnv ".env.testing" "STRIPE_SECRET" "sk_test_0"
		replaceEnv ".env.testing" "STRIPE_WEBHOOK_SECRET" "whsec_0"
		replaceEnv ".env.testing" "STRIPE_SUBSCRIPTION_DEFAULT" "price_0"
	fi



#///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
#/
#/	MISC
#/
#///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	printBanner "MISC"

	# load a dump of data if available
	dumpFile=$(find . -maxdepth 1 -name "dump.zip")

	if [[ -f "$dumpFile" ]]; then
		readinput -p "Dump of data found. Use this data? (y/n):  " -e useDump
		useDump="$(convertToBool "$useDump")"
	else
		useDump="false"
	fi



#///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
#/
#/	DEPENDENCIES
#/
#///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	printBanner "INSTALL NODE MODULES"

	npx --yes update-browserslist-db@latest
	rm -rf node_modules

	if command -v bun &>/dev/null; then
		bun install
	else
		npm install
		npm audit fix
	fi


	printBanner "INSTALL COMPOSER PACKAGES"

	rm -rf vendor
	if [[ "$appDomain" == "localhost" ]]; then
		composer install
	else
		composer install --optimize-autoloader
	fi


	printBanner "LARAVEL ARTISAN"

	php artisan key:generate
	php artisan key:generate --env=testing
	php artisan storage:link
	php artisan migrate:fresh --seed
	php artisan migrate:fresh --seed --env=testing

	if [[ "$useDump" == "true" ]]; then

		php artisan dump:load
	fi



#///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
#/
#/	FINALIZE
#/
#///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	##################################
	# test vite compile
	##################################

	if [[ "$appDomain" == "localhost" ]]; then

		printBanner "VITE COMPILE TEST"

		npm run routes
		npm run production
	fi


	##################################
	# overwrite .env for production
	##################################

	if [[ "$appDomain" != "localhost" ]]; then

		replaceEnv ".env" "APP_ENV" "production"
		replaceEnv ".env" "APP_DEBUG" "false"
		replaceEnv ".env" "DEBUGBAR_ENABLED" "false"
		replaceEnv ".env" "COOKIE_CONSENT_ENABLED" "true"
		replaceEnv ".env" "CACHE_STORE" "redis"
		replaceEnv ".env" "SESSION_DRIVER" "redis"
		replaceEnv ".env" "SESSION_SECURE_COOKIE" "true"
		replaceEnv ".env" "QUEUE_CONNECTION" "redis"
		replaceEnv ".env" "SCOUT_DRIVER" "meilisearch"
	fi


	printf "\n\n\n"
	printf "${GREEN}INSTALLATION COMPLETED${NC}\n"
	printf "\n\n\n"
