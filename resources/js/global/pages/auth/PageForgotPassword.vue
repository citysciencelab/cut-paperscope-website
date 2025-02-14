<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<section class="content auth">

			<h2>{{ t('headline') }}</h2>
			<p>{{ t('copy') }}</p>

			<!-- FORM -->
			<form-errors v-if="!showSuccess" :errors="errors"/>
			<input-text v-if="!showSuccess" label="E-Mail" id="email-forgot" v-model="form.email" :error="errors.email" @enter="submit" autofocus required/>

			<!-- BUTTONS -->
			<div v-if="!showSuccess" class="form-row-buttons">
				<btn ref="submitBtn" class="btn-forgot" :label="t('button')" @click="submit" blocking/>
			</div>

			<!-- SUCCESS -->
			<h4 v-if="showSuccess" class="forgot-password-success">
				{{ t("success") }}
			</h4>

			<router-link :to="link('login')" class="textlink">{{ t('login') }}</router-link>

		</section>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, useTemplateRef } from 'vue';
		import { usePage } from '@global/composables/usePage';
		import { useForm } from '@global/composables/useForm';
		import { useAuth } from '@global/composables/useAuth';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const { form, errors } = useForm();
		const { forgotPassword } = useAuth();

		const { t } = usePage("headline");


		/////////////////////////////////
		// FORM
		/////////////////////////////////

		const submitBtn = useTemplateRef('submitBtn');
		const showSuccess = ref(false);

		function submit() {

			// disable button if via autofill
			submitBtn.value.setLoading(true);

			// submit form
			forgotPassword(form.value).then(response => {
				showSuccess.value = true;
			})
			.catch(e => {
				errors.value = e.response.data.errors;
				submitBtn.value.setLoading(false);
			});
		}


	</script>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    LANG
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<i18n lang="json5">
		{
			"de": {
				"headline": "Passwort vergessen",
				"copy": "Wenn Du Dein Passwort vergessen hast, kannst Du hier einfach Deine E-Mail-Adresse eintragen und absenden. Wir schicken Dir dann einen Link per E-Mail, über den Du ganz einfach ein neues Passwort festlegen kannst.",
				"button": "Bestätigungslink senden",
				"success": "Ein Link zum Zurücksetzen wurde an Deine E-Mail-Adresse gesendet.",
				"login": "Zurück zum Login",
			},
			"en": {
				"headline": "Forgot password",
				"copy": "If you have forgotten your password, simply enter your email here and submit it. We will then send you a link by email, via which you can easily set a new password.",
				"button": "Send confirmation link",
				"success": "A link to reset has been sent to your email address.",
				"login": "Back to login",
			}
		}
	</i18n>



