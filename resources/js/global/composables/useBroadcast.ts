/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// vue
	import { ref } from 'vue';
	import Echo from 'laravel-echo';
	import Pusher from 'pusher-js';

	// app
	import { useConfig } from '@global/composables/useConfig';
	import { useUser } from '@global/composables/useUser';



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	COMPOSABLE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


export const useBroadcast = () => {


	/////////////////////////////////
	// INIT
	/////////////////////////////////

	const { baseUrl } = useConfig();
	const { user } = useUser();


	/////////////////////////////////
	// WEBSOCKET
	/////////////////////////////////

	const socketConnected = ref(false);
	const reverb: any = window.config.reverb;

	const auth = {
		endpoint: baseUrl + 'api/broadcasting/auth',
		headers:{}
	}

	function initWebsocket() {

		if(!window.Echo) {

			window.Pusher = Pusher;
			window.Echo = new Echo({
				broadcaster: 'reverb',
				userAuthentication: auth,
				channelAuthorization: auth,
				key: reverb.key,
				wsHost: reverb.host,
				wsPort: reverb.port,
				wssPort: reverb.port,
				forceTLS: reverb.forceTLS,
				enabledTransports: ['ws', 'wss'],
			});

			initNotifications();
		}
		else {
			socketConnected.value = window.Echo.connector.pusher.connection.state === 'connected';
		}
	}

	initWebsocket();

	function toggleWebsocket() {

		if(socketConnected.value) {
			window.Echo.connector.pusher.disconnect();
		}
		else {
			window.Echo.connector.pusher.connect();
		}
	}


	/////////////////////////////////
	// EVENTS
	/////////////////////////////////

	window.Echo.connector.pusher.connection.bind('connected', () => {
		socketConnected.value = true;
	});

	window.Echo.connector.pusher.connection.bind('disconnected', () => {
		socketConnected.value = false;
	});


	/////////////////////////////////
	// NOTIFICATIONS
	/////////////////////////////////

	const notificationCallbacks: Function[] = [];

	function initNotifications() {

		window.Echo.private(`App.Models.Auth.User.${user.value.id}`).notification(notificationCallback);
	}

	function notificationCallback(data: any) {

		notificationCallbacks.forEach(callback => callback(data));
	}

	function onNotification(callback: Function) {

		notificationCallbacks.push(callback);
	}


	/////////////////////////////////
	// CHANNELS
	/////////////////////////////////

	function subscribePrivateChannel(channel: string, callback: Function) {

		window.Echo?.private(channel).listenToAll((event: string, data: any) => {

			event = event.replace('.client-', '');
			callback(event, data);
		});
	}

	function sendPrivateChannel(channel: string, event: string, data: any) {

		window.Echo?.private(channel).whisper(event, data);
	}


	/////////////////////////////////
	// EXPORT
	/////////////////////////////////

	return {
		socketConnected, toggleWebsocket,
		onNotification,
		subscribePrivateChannel, sendPrivateChannel
	};



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


};
