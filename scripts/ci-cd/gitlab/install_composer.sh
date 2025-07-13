#!/bin/bash

#///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
#/
#/	COMPOSER INSTALL
#/
#///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


 	composer install --prefer-dist --no-ansi --no-interaction --no-progress --no-scripts

	# remove unused aws packages
	composer run-script pre-autoload-dump



#///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
#/
#/	CLEANUP VENDOR
#/
#///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	for package in $(find vendor -maxdepth 2 -mindepth 1 -type d); do

		# remove unnecessary folder
		[ -d "$package/examples" ] && rm -rf "$package/examples"
		[ -d "$package/docs" ] && rm -rf "$package/docs"

		# remove unnecessary files
		[ -f "$package/Readme.md" ] && rm -f "$package/Readme.md"
		[ -f "$package/README.md" ] && rm -f "$package/README.md"
		[ -f "$package/README.md" ] && rm -f "$package/README.md"
		[ -f "$package/readme.md" ] && rm -f "$package/readme.md"
		[ -f "$package/CHANGELOG.md" ] && rm -f "$package/CHANGELOG.md"
		[ -f "$package/LICENSE.md" ] && rm -f "$package/LICENSE.md"
		[ -f "$package/LICENSE.txt" ] && rm -f "$package/LICENSE.txt"
		[ -f "$package/LICENSE" ] && rm -f "$package/LICENSE"
		[ -f "$package/HISTORY.md" ] && rm -f "$package/HISTORY.md"
		[ -f "$package/SECURITY.md" ] && rm -f "$package/SECURITY.md"
		[ -f "$package/CMakeLists.txt" ] && rm -f "$package/CMakeLists.txt"

	done



#///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
#/
#/	CLEANUP PACKAGES
#/
#///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	[ -d "vendor/laravel/pint" ] && rm -rf "vendor/laravel/pint"
	[ -d "vendor/getbrevo/brevo-php/test" ] && rm -rf "vendor/getbrevo/brevo-php/test"
	[ -d "vendor/getbrevo/brevo-php/docs" ] && rm -rf "vendor/getbrevo/brevo-php/docs"


	# cleanup fakerphp
	if [ -d "vendor/fakerphp/faker/src/Faker/Provider" ]; then

		# delete every folder except the en_US and de_DE folder
		find vendor/fakerphp/faker/src/Faker/Provider/* -maxdepth 0 -type d ! -name 'en_US' ! -name 'de_DE' -exec rm -rf {} \;
	fi

