#!/bin/bash
clear



#///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
#/
#/	HELPER
#/
#///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


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

		echo "Updated $envKey with $envValue"
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



#///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
#/
#/	.ENV
#/
#///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	# create local .env file
	cp .env.example .env

	# laravel
	replaceEnv ".env" "APP_KEY" "base64:u84x+xcKL/oDefGwXcuFWJojvPHbSwCIGPuturOtVN4="

	# project features
	replaceEnv ".env" "FEATURE_BACKEND" "$FEATURE_BACKEND"
	replaceEnv ".env" "FEATURE_BACKEND_RESET" "$FEATURE_BACKEND_RESET"
	replaceEnv ".env" "FEATURE_APP_ACCOUNTS" "$FEATURE_APP_ACCOUNTS"
	replaceEnv ".env" "FEATURE_SHOP" "$FEATURE_SHOP"
	replaceEnv ".env" "FEATURE_MULTI_LANG" "$FEATURE_MULTI_LANG"

	# cesium
	replaceEnv ".env" "CESIUM_ION_TOKEN" "$CESIUM_ION_TOKEN"

	# reverb
	replaceEnv ".env" "REVERB_APP_KEY" "$REVERB_APP_KEY"
	replaceEnv ".env" "REVERB_HOST" "$REVERB_HOST"
	replaceEnv ".env" "REVERB_PORT" "$REVERB_PORT"
	replaceEnv ".env" "REVERB_SCHEME" "$REVERB_SCHEME"



#///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
#/
#/	.ENV.TESTING
#/
#///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	cp .env .env.testing

	replaceEnv ".env.testing" "APP_KEY" "base64:1OqmQdaua03OHuRQLj7bHxkuuNW6+sP2LN3OH0/bKdw="

	replaceEnv ".env.testing" "APP_ENV" "local"
	replaceEnv ".env.testing" "APP_DEBUG" "true"
	replaceEnv ".env.testing" "ROOT_PASSWORD" "password"
	replaceEnv ".env.testing" "DB_CONNECTION" "testing"
	replaceEnv ".env.testing" "DB_PASSWORD" "local"
	replaceEnv ".env.testing" "COOKIE_CONSENT_ENABLED" "false"
	replaceEnv ".env.testing" "CACHE_STORE" "file"
	replaceEnv ".env.testing" "SESSION_SECURE_COOKIE" "false"
	replaceEnv ".env.testing" "QUEUE_CONNECTION" "sync"
	replaceEnv ".env.testing" "FILESYSTEM_DISK" "testing"

	replaceEnv ".env.testing" "STRIPE_KEY" "pk_test_0"
	replaceEnv ".env.testing" "STRIPE_SECRET" "sk_test_0"
	replaceEnv ".env.testing" "STRIPE_WEBHOOK_SECRET" "whsec_0"
	replaceEnv ".env.testing" "STRIPE_SUBSCRIPTION_DEFAULT" "price_0"


