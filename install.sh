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
			sed -i '' "s,^$envKey=.*,$envKey=$envValue,g" $envFile
		else
			sed -i "s,^$envKey=.*,$envKey=$envValue,g" $envFile
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
	replaceEnv ".env" "APP_NAME" "\"$appName\""

	# read APP_URL
	readinput -p "Set APP_URL (include 'public' on localhost):  " -i "http://localhost/client/project/public/" -e appUrlRaw

	# append trailing "/" to APP_URL
	[[ "${appUrlRaw}" != */ ]] && appUrlRaw="${appUrlRaw}/"

	# set APP_URL
	appUrl="$(sed -E 's/\//\\\//g' <<<$appUrlRaw)"
	replaceEnv ".env" "APP_URL" "$appUrl"
	replaceTextInFile "tests/Backstop/backstop.config.cjs" "https://wwww.paperscope.de/" "$appUrl"

	# get domain from APP_URL
	appDomain="$(sed -E 's/^(http[s]?:\/\/)([a-z0-9:.-]{1,})(.*)/\2/' <<<$appUrlRaw)"
	appDomain="$(sed -E 's/\//\\\//g' <<<$appDomain)"

	# set SESSION_DOMAIN
	replaceEnv ".env" "SESSION_DOMAIN" "$appDomain"

	# set ROOT_PASSWORD
	readinput -p "Set password for ROOT user:  " -e rootPassword
	replaceEnv ".env" "ROOT_PASSWORD" "\"$rootPassword\""

	# set database credentials
	readinput -p "Name for database:  " -e dbName
	replaceEnv ".env" "DB_DATABASE" "\"$dbName\""
	readinput -p "Username for database:  " -e dbUser
	replaceEnv ".env" "DB_USERNAME" "\"$dbUser\""
	readinput -p "Password for database:  " -e dbPassword
	replaceEnv ".env" "DB_PASSWORD" "\"$dbPassword\""

	# set queue names
	queueSlug=$(slugify "${appName}_queue")
	replaceEnv ".env" "SQS_QUEUE" "$queueSlug"
	replaceEnv ".env" "REDIS_QUEUE" "$queueSlug"



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
	npm install
	npm audit fix


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
