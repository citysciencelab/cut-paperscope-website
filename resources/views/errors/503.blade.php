<!DOCTYPE html>
<html lang="de">

	<head>

		<title>{{ config('app.name') }} | {{ __('maintenance.title') }}</title>

		<!-- META -->
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta data-vmid="description" name="description" content="{{ __('maintenance.copy') }}">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="robots" content="noindex,nofollow" />

		<!-- ICONS -->
		<!-- https://realfavicongenerator.net -->
		<link rel="apple-touch-icon" sizes="180x180" href="{{asset('/apple-touch-icon.png')}}">
		<link rel="icon" type="image/png" sizes="32x32" href="{{asset('/favicon-32x32.png')}}">
		<link rel="icon" type="image/png" sizes="16x16" href="{{asset('/favicon-16x16.png')}}">
		<link rel="manifest" href="{{asset('/site.webmanifest')}}">
		<link rel="mask-icon" href="{{asset('/safari-pinned-tab.svg')}}" color="#5bbad5">
		<meta name="msapplication-TileColor" content="#da532c">
		<meta name="theme-color" content="#ffffff">

		<!-- FONTS -->
		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

		<!-- CSS -->
		@php

			// find css file in public/css folder
			$cssFolder = public_path('build/css/');
			$cssFiles = scandir($cssFolder);

			// add css file
			foreach ($cssFiles as $cssFile) {
				if(str_starts_with($cssFile,'app-')) {

					echo '<link rel="stylesheet" href="./build/css/' . $cssFile . '">';
					break;
				}
			}

		@endphp


	</head>


<!-- ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// -->


	<body>

		<!-- APP CONTENT -->
		<div id="app">
			<main class="page">
				<div class="content" style="height:100vh;display:flex;align-items:center;">
					<section>
						<h3 style="text-align:center;font-weight:bold;">{{ __('maintenance.title') }}</h3>
						<p style="text-align:center;max-width:500px;margin:0 auto">{{ __('maintenance.copy') }}</p>
					</section>
				</div>
			</main>
		</div>


	</body>

</html>
