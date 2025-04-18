<?php

/*
|--------------------------------------------------------------------------
| Validation Language Lines
|--------------------------------------------------------------------------
|
| The following language lines contain the default error messages used by
| the validator class. Some of these rules have multiple versions such
| as the size rules. Feel free to tweak each of these messages here.
|
*/

return [
	'accepted'             => ':attribute muss akzeptiert werden.',
	'accepted_if'          => ':attribute muss akzeptiert werden, wenn :other :value ist.',
	'active_url'           => ':attribute ist keine gültige Internet-Adresse.',
	'after'                => ':attribute muss ein Datum nach :date sein.',
	'after_or_equal'       => ':attribute muss ein Datum nach :date oder gleich :date sein.',
	'alpha'                => ':attribute darf nur aus Buchstaben bestehen.',
	'alpha_dash'           => ':attribute darf nur aus Buchstaben, Zahlen, Binde- und Unterstrichen bestehen.',
	'alpha_num'            => ':attribute darf nur aus Buchstaben und Zahlen bestehen.',
	'array'                => ':attribute muss ein Array sein.',
	'before'               => ':attribute muss ein Datum vor :date sein.',
	'before_or_equal'      => ':attribute muss ein Datum vor :date oder gleich :date sein.',
	'between'              => [
		'array'   => ':attribute muss zwischen :min & :max Elemente haben.',
		'file'    => ':attribute muss zwischen :min & :max Kilobytes groß sein.',
		'numeric' => ':attribute muss zwischen :min & :max liegen.',
		'string'  => ':attribute muss zwischen :min & :max Zeichen lang sein.',
	],
	'boolean'              => ':attribute muss entweder \'true\' oder \'false\' sein.',
	'confirmed'            => ':attribute stimmt nicht mit der Bestätigung überein.',
	'current_password'     => 'Das Passwort ist falsch.',
	'date'                 => ':attribute muss ein gültiges Datum sein.',
	'date_equals'          => ':attribute muss ein Datum gleich :date sein.',
	'date_format'          => ':attribute entspricht nicht dem gültigen Format für :format.',
	'declined'             => ':attribute muss abgelehnt werden.',
	'declined_if'          => ':attribute muss abgelehnt werden wenn :other :value ist.',
	'different'            => ':attribute und :other müssen sich unterscheiden.',
	'digits'               => ':attribute muss :digits Stellen haben.',
	'digits_between'       => ':attribute muss zwischen :min und :max Stellen haben.',
	'dimensions'           => ':attribute hat ungültige Bildabmessungen.',
	'distinct'             => ':attribute beinhaltet einen bereits vorhandenen Wert.',
	'email'                => ':attribute muss eine gültige E-Mail-Adresse sein.',
	'ends_with'            => ':attribute muss eine der folgenden Endungen aufweisen: :values',
	'enum'                 => 'The selected :attribute is invalid.',
	'exists'               => 'Der gewählte Wert für :attribute ist ungültig.',
	'file'                 => ':attribute muss eine Datei sein.',
	'filled'               => ':attribute muss ausgefüllt sein.',
	'gt'                   => [
		'array'   => ':attribute muss mehr als :value Elemente haben.',
		'file'    => ':attribute muss größer als :value Kilobytes sein.',
		'numeric' => ':attribute muss größer als :value sein.',
		'string'  => ':attribute muss länger als :value Zeichen sein.',
	],
	'gte'                  => [
		'array'   => ':attribute muss mindestens :value Elemente haben.',
		'file'    => ':attribute muss größer oder gleich :value Kilobytes sein.',
		'numeric' => ':attribute muss größer oder gleich :value sein.',
		'string'  => ':attribute muss mindestens :value Zeichen lang sein.',
	],
	'image'                => ':attribute muss ein Bild sein.',
	'in'                   => 'Der gewählte Wert für :attribute ist ungültig.',
	'in_array'             => 'Der gewählte Wert für :attribute kommt nicht in :other vor.',
	'integer'              => ':attribute muss eine ganze Zahl sein.',
	'ip'                   => ':attribute muss eine gültige IP-Adresse sein.',
	'ipv4'                 => ':attribute muss eine gültige IPv4-Adresse sein.',
	'ipv6'                 => ':attribute muss eine gültige IPv6-Adresse sein.',
	'json'                 => ':attribute muss ein gültiger JSON-String sein.',
	'lt'                   => [
		'array'   => ':attribute muss weniger als :value Elemente haben.',
		'file'    => ':attribute muss kleiner als :value Kilobytes sein.',
		'numeric' => ':attribute muss kleiner als :value sein.',
		'string'  => ':attribute muss kürzer als :value Zeichen sein.',
	],
	'lte'                  => [
		'array'   => ':attribute darf maximal :value Elemente haben.',
		'file'    => ':attribute muss kleiner oder gleich :value Kilobytes sein.',
		'numeric' => ':attribute muss kleiner oder gleich :value sein.',
		'string'  => ':attribute darf maximal :value Zeichen lang sein.',
	],
	'mac_address'          => 'The :attribute must be a valid MAC address.',
	'max'                  => [
		'array'   => ':attribute darf maximal :max Elemente haben.',
		'file'    => ':attribute darf maximal :max Kilobytes groß sein.',
		'numeric' => ':attribute darf maximal :max sein.',
		'string'  => ':attribute darf maximal :max Zeichen haben.',
	],
	'mimes'                => ':attribute muss den Dateityp :values haben.',
	'mimetypes'            => ':attribute muss den Dateityp :values haben.',
	'min'                  => [
		'array'   => ':attribute muss mindestens :min Elemente haben.',
		'file'    => ':attribute muss mindestens :min Kilobytes groß sein.',
		'numeric' => ':attribute muss mindestens :min sein.',
		'string'  => ':attribute muss mindestens :min Zeichen lang sein.',
	],
	'multiple_of'          => ':attribute muss ein Vielfaches von :value sein.',
	'not_in'               => 'Der gewählte Wert für :attribute ist ungültig.',
	'not_regex'            => ':attribute hat ein ungültiges Format.',
	'numeric'              => ':attribute muss eine Zahl sein.',
	'password'             => 'Das Passwort ist falsch.',
	'present'              => ':attribute muss vorhanden sein.',
	'prohibited'           => ':attribute ist unzulässig.',
	'prohibited_if'        => ':attribute ist unzulässig, wenn :other :value ist.',
	'prohibited_unless'    => ':attribute ist unzulässig, wenn :other nicht :values ist.',
	'prohibits'            => ':attribute verbietet die Angabe von :other.',
	'regex'                => ':attribute Format ist ungültig.',
	'required'             => ':attribute muss ausgefüllt werden.',
	'required_if'          => ':attribute muss ausgefüllt werden, wenn :other den Wert :value hat.',
	'required_unless'      => ':attribute muss ausgefüllt werden, wenn :other nicht den Wert :values hat.',
	'required_with'        => ':attribute muss ausgefüllt werden, wenn :values ausgefüllt wurde.',
	'required_with_all'    => ':attribute muss ausgefüllt werden, wenn :values ausgefüllt wurde.',
	'required_without'     => ':attribute muss ausgefüllt werden, wenn :values nicht ausgefüllt wurde.',
	'required_without_all' => ':attribute muss ausgefüllt werden, wenn keines der Felder :values ausgefüllt wurde.',
	'same'                 => ':attribute und :other müssen übereinstimmen.',
	'size'                 => [
		'array'   => ':attribute muss genau :size Elemente haben.',
		'file'    => ':attribute muss :size Kilobyte groß sein.',
		'numeric' => ':attribute muss gleich :size sein.',
		'string'  => ':attribute muss :size Zeichen lang sein.',
	],
	'starts_with'          => ':attribute muss mit einem der folgenden Anfänge aufweisen: :values',
	'string'               => ':attribute muss ein String sein.',
	'timezone'             => ':attribute muss eine gültige Zeitzone sein.',
	'unique'               => ':attribute ist bereits vergeben.',
	'uploaded'             => ':attribute konnte nicht hochgeladen werden.',
	'url'                  => ':attribute muss eine URL sein.',
	'uuid'                 => ':attribute muss ein UUID sein.',
	'domain'               => ':attribute muss zur Domain :domain gehören',

	/*
	|--------------------------------------------------------------------------
	| Custom Validation Language Lines
	|--------------------------------------------------------------------------
	|
	| Here you may specify custom validation messages for attributes using the
	| convention "attribute.rule" to name the lines. This makes it quick to
	| specify a specific custom language line for a given attribute rule.
	|
	*/

	'custom' => [
		'attribute-name' => [
			'rule-name' => 'custom-message',
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Custom Validation Attributes
	|--------------------------------------------------------------------------
	|
	| The following language lines are used to swap our attribute placeholder
	| with something more reader friendly such as "E-Mail Address" instead
	| of "email". This simply helps us make our message more expressive.
	|
	*/

	'attributes' => [
		'name'                  => 'Name',
		'username'              => 'Benutzername',
		'email'                 => 'E-Mail-Adresse',
		'first_name'            => 'Vorname',
		'last_name'             => 'Nachname',
		'surname'               => 'Nachname',
		'username'              => 'Benutzername',
		'password'              => 'Passwort',
		'password_confirmation' => 'Passwort-Bestätigung',
		'city'                  => 'Stadt',
		'country'               => 'Land',
		'address'               => 'Adresse',
		'phone'                 => 'Telefonnummer',
		'mobile'                => 'Handynummer',
		'age'                   => 'Alter',
		'sex'                   => 'Geschlecht',
		'gender'                => 'Geschlecht',
		'day'                   => 'Tag',
		'month'                 => 'Monat',
		'year'                  => 'Jahr',
		'hour'                  => 'Stunde',
		'minute'                => 'Minute',
		'second'                => 'Sekunde',
		'file' 					=> 'Datei',
		'image'					=> 'Bild',
		'folder'				=> 'Ordner',
		'storage' 				=> 'Storage',
		'content'               => 'Inhalt',
		'title'                 => 'Titel',
		'description'           => 'Beschreibung',
		'message'				=> 'Nachricht',
		'excerpt'               => 'Auszug',
		'date'                  => 'Datum',
		'time'                  => 'Uhrzeit',
		'available'             => 'verfügbar',
		'size'                  => 'Größe',
		'street' 				=> 'Straße',
		'street_number' 		=> 'Hausnummer',
		'zipcode' 				=> 'Postleitzahl',
		'city' 					=> 'Stadt',
		'device_name' 			=> 'Gerätename',
	],

];
