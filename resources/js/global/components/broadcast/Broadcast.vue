<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template></template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { useConfig } from '@global/composables/useConfig';
		import { useUser } from '@global/composables/useUser';
		import { useApi } from '@global/composables/useApi';

		import Echo from 'laravel-echo';
		import Pusher from 'pusher-js';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const { baseUrl } = useConfig();
		const { user } = useUser();
		const { apiPost } = useApi();

		const props = defineProps({
			project: {type: Object, required: true},
		});

		const emit = defineEmits(['updatedProject']);


		/////////////////////////////////
		// WEBSOCKET
		/////////////////////////////////

		function initBroadcast() {

			window.Pusher = Pusher;

			window.Echo = new Echo({
				broadcaster: 'reverb',
				userAuthentication: {
					endpoint: baseUrl + 'api/broadcasting/auth',
					headers:{}
				},
				channelAuthorization: {
					endpoint: baseUrl + 'api/broadcasting/auth',
					headers: {}
				},
				key: import.meta.env.VITE_REVERB_APP_KEY,
				wsHost: import.meta.env.VITE_REVERB_HOST,
				wsPort: import.meta.env.VITE_REVERB_PORT,
				wssPort: import.meta.env.VITE_REVERB_PORT,
				forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
				enabledTransports: ['ws', 'wss'],
			});

			initNotifications();
			initChannels();
		}

		initBroadcast();


		/////////////////////////////////
		// NOTIFICATIONS
		/////////////////////////////////

		function initNotifications() {

			//window.Echo.private(`App.Models.Auth.User.${user.value.id}`).notification(onUserNotification);
		}

		function onUserNotification(notification) {

			console.log('user notification', notification);
		}


		/////////////////////////////////
		// CHANNELS
		/////////////////////////////////

		function initChannels() {

			window.Echo.private('project.'+props.project.slug).listen('ProjectSceneUpdated', onProjectUpdated);
		}

		function onProjectUpdated(project) {

			emit('updatedProject', project);
		}

		function sendProjectMessage(event, data) {

			console.log('send project message', event, data);
			window.Echo.private('project.'+props.project.slug).whisper(event, data);
		}

		defineExpose({ sendProjectMessage });


	</script>


