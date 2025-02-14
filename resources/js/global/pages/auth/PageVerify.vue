<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<section class="content auth">

			<h1>{{ t("Registrierung abschließen") }}</h1>
			<p>{{ t("register_copy") }}</p>

			<div class="form-row-buttons" v-fade="!isSuccess">
				<btn ref="resendBtn" class="btn-resend small secondary" :label="t('Bestätigungslink nochmal senden')" @click="resend" blocking/>
			</div>

		</section>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, useTemplateRef, watch } from 'vue';
		import { usePage } from '@global/composables/usePage';
		import { useUser } from '@global/composables/useUser';
		import { useAuth } from '@global/composables/useAuth';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const { t } = usePage("Registrierung abschließen");
		const { user, userIsGuest } = useUser();
		const { resendVerify, redirectToHome } = useAuth();


		/////////////////////////////////
		// FORM
		/////////////////////////////////

		const resendBtn = useTemplateRef('resendBtn');
		const isSuccess = ref(false);

		function resend() {

			const data = {
				email: user.value.email,
			};

			// submit form
			resendVerify(data).then(response => {
				isSuccess.value = true;
			})
			.catch(e => {
				resendBtn.value.setLoading(false);
			});
		}


		/////////////////////////////////
		// VERIFIED
		/////////////////////////////////

		watch(userIsGuest, v => (!v) ? redirectToHome(): null, {immediate:true});


	</script>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    LANG
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<i18n lang="json5">
		{
			"de": {
				"register_copy": "Wir haben Dir einen Bestätigungslink an Deine E-Mail-Adresse geschickt. Bitte folge dem Link in Deiner E-Mail, um die Registrierung abzuschließen.",
			},
			"en": {
				"Registrierung abschließen": "Complete registration",
				"register_copy": "We have sent you a confirmation link to your email address. Please follow the link in your email to complete the registration.",
				"Bestätigungslink nochmal senden": "Send confirmation link again",
			}
		}
	</i18n>
