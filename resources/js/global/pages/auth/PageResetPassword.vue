<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<section class="content auth">

			<h2>{{ t('headline') }}</h2>

			<!-- FORM -->
			<form-errors v-if="!showSuccess" :errors="errors"/>
			<input-text v-if="!showSuccess" type="password" label="Neues Passwort" info="min. 8 Zeichen" id="password-reset" v-model="form.password" :error="errors.password" autofocus required/>
			<input-text v-if="!showSuccess" type="password" label="Passwort wiederholen" id="password-confirmation" v-model="form.password_confirmation" :error="errors.password_confirmation" @enter="submit" required/>

			<!-- BUTTONS -->
			<div v-if="!showSuccess" class="form-row-buttons">
				<btn ref="submitBtn" class="btn-reset" :label="t('button')" @click="submit" blocking/>
			</div>

			<!-- SUCCESS -->
			<h4 v-if="showSuccess" class="reset-password-success">
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
		import { useRoute } from 'vue-router';
		import { usePage } from '@global/composables/usePage';
		import { useForm } from '@global/composables/useForm';
		import { useAuth } from '@global/composables/useAuth';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const route = useRoute();
		const { form, errors } = useForm();
		const { resetPassword } = useAuth();

		const { t } = usePage("headline");


		/////////////////////////////////
		// FORM
		/////////////////////////////////

		const submitBtn = useTemplateRef("submitBtn");
		const showSuccess = ref(false);

		function submit() {

			// set form reset params
			form.value.token = route.query.token;
			form.value.email = route.query.email;

			// disable button if via autofill
			submitBtn.value.setLoading(true);

			// submit form
			resetPassword(form.value).then(response => {

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
				"headline": "Passwort zurücksetzen",
				"button": "Passwort zurücksetzen",
				"success": "Das Passwort wurde zurückgesetzt.",
				"login": "Zurück zum Login",
			},
			"en": {
				"headline": "Reset password",
				"button": "Reset password",
				"success": "The password has been reset.",
				"login": "Back to login",
			}
		}
	</i18n>



