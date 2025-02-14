<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

	<head>

		<title>{{ config('app.name') }} | Backend</title>

		<!-- META -->
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="csrf-token" content="{{ csrf_token() }}">
		<meta name="robots" content="noindex,nofollow">

		<!-- ICONS -->
		<!-- https://realfavicongenerator.net -->
		<link rel="icon" type="image/png" href="{{asset('/favicon-96x96.png')}}" sizes="96x96" />
		<link rel="icon" type="image/svg+xml" href="{{asset('/favicon.svg')}}" />
		<link rel="shortcut icon" href="{{asset('/favicon.ico')}}" />
		<link rel="apple-touch-icon" sizes="180x180" href="{{asset('/apple-touch-icon.png')}}" />
		<link rel="manifest" href="{{asset('/site.webmanifest')}}">
		<meta name="msapplication-TileColor" content="#ffffff">
		<meta name="theme-color" content="#ffffff">

		<!-- CSS -->
		@vite('resources/sass/backend/backend.scss')
		@stack('styles')

		<!-- JAVASCRIPT -->
		<script>
			window.config = {!! json_encode($config, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!};
		</script>

	</head>


<!-- ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// -->


	<body>

		<!-- APP CONTENT -->
		<div id="app"></div>

		<!-- SCRIPTS -->
		<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js" integrity="sha256-78hcfrFBgZcXzaADNISoSxyJDROwLjVaL+x51CSyDno=" crossorigin="anonymous"></script>
		@vite('resources/js/backend/Backend.js')
		@stack('scripts')

	</body>

</html>
