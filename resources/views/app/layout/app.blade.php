<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

	<head>

		<title>{{ empty($meta['title']) ? config('app.name') : $meta['title'] }}</title>

		<!-- META -->
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="description" content="{{ $meta['description'] }}" data-vmid="description">
		<link rel="canonical" href="{{ $meta['canonical'] }}">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="csrf-token" content="{{ csrf_token() }}">
		@if(config('app.env')!="production" || str_contains(config('app.url'),'//dev.'))
			<meta name="robots" content="noindex,nofollow">
		@endif

		<!-- SOCIAL -->
		<meta property="og:type" content="{{$meta['og:type']}}">
		<meta property="og:url" content="{{$meta['og:url']}}">
		<meta property="og:title" content="{{$meta['og:title']}}">
		<meta property="og:description" content="{{$meta['og:description']}}">
		@if(isset($meta['og:image']))
			<meta property="og:image" content="{{$meta['og:image']}}">
			<meta name="twitter:image"  content="{{ str_replace('.jpg','-twitter.jpg',$meta['og:image']) }}">
			<meta name="twitter:image:alt" content="{{$meta['og:description']}}">
		@endif
		<meta property="og:locale" content="{{$meta['og:locale']}}">
		@foreach($meta['languages'] as $lang)
			<meta property="og:locale:alternate" content="{{$lang}}">
		@endforeach
		<meta name="twitter:card" content="summary_large_image">
		<meta name="twitter:title" content="{{$meta['og:title']}}">
		<meta name="twitter:description"  content="{{$meta['og:description']}}">

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
		@vite('resources/sass/app/app.scss')
		@stack('styles')

		<!-- JAVASCRIPT -->
		<script>
			window.config = {!! json_encode($config, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!};
			window.pages = {!! json_encode($pages, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!};
		</script>

	</head>


<!-- ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// -->


	<body>

		<!-- APP CONTENT -->
		<div id="app">
			<img width="9829" height="9829" style="pointer-events:none;position:absolute;top:0;left:0.01vw;width:89.2vw;height:99vh;max-width:91.1vw;max-height:99vh;" src="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz48c3ZnIHdpZHRoPSI5OTk5OXB4IiBoZWlnaHQ9Ijk5OTk5cHgiIHZpZXdCb3g9IjAgMCA5OTk5OSA5OTk5OSIgdmVyc2lvbj0iMS4xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIj48ZyBzdHJva2U9Im5vbmUiIGZpbGw9Im5vbmUiIGZpbGwtb3BhY2l0eT0iMCI+PHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9Ijk5OTk5IiBoZWlnaHQ9Ijk5OTk5Ij48L3JlY3Q+IDwvZz4gPC9zdmc+" alt="init website">
		</div>

		<!-- SCRIPTS -->
		<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.7/dist/gsap.min.js" integrity="sha256-VGihm4idI0E1uqrWezWsiScB9F/jMosn7qs6pPSkeac=" crossorigin="anonymous"></script>
		{{-- <script async src="https://js.stripe.com/v3/" integrity="sha256-AKM7BHFHpyRx0YOILADZN2Hr3abkbn5zrusYYxUuwzQ=" crossorigin="anonymous"></script> --}}

		@vite('resources/js/app/App.js')
		@stack('scripts')

	</body>

</html>
