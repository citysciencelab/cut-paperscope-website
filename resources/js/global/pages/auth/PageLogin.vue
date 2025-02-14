<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<Teleport v-if="webContext == 'app'" to="#app">
			<div class="login-paperscope-icon"></div>
		</Teleport>

		<section class="content auth" v-if="!isRedirect">

			<svg-item v-if="webContext == 'app'" class="login-paperscope-logo" inline="app/paperscope-logo"/>
			<h1 v-else>Login</h1>


			<!-- FORM -->
			<form-errors :errors="errors"/>
			<input-text
				:label="webContext == 'backend' ? t('E-Mail / Benutzername') : null"
				:placeholder="webContext == 'app' ? t('E-Mail / Benutzername') : null"
				id="email"
				v-model="form"
				:error="errors"
				:max-length="100"
				autofocus
				required
			/>
			<input-text
				:label="webContext == 'backend' ? t('Passwort') : null"
				:placeholder="webContext == 'app' ? t('Passwort') : null"
				id="password"
				@enter="submit"
				v-model="form"
				:error="errors"
				:max-length="50"
				type="password"
				required
			/>
			<router-link v-if="webContext=='backend'" :to="link('password.forgot')" class="btn-forgot textlink">{{ t('Passwort vergessen') }}</router-link>

			<!-- BUTTONS -->
			<div class="form-row-buttons">
				<btn ref="loginBtn" class="cta btn-login" label="Login" @click="submit" blocking/>
			</div>

			<!-- <sso-buttons/> -->

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
		const { form, errors, scrollToErrors } = useForm();
		const { login } = useAuth();

		const { t, redirectIfUser } = usePage("Login");

		const isRedirect = ref( redirectIfUser() );


		/////////////////////////////////
		// FORM
		/////////////////////////////////

		const loginBtn = useTemplateRef('loginBtn');

		function submit() {

			// disable button if via autofill
			loginBtn.value.setLoading(true);

			// force "keep me logged in"
			form.value.remember = true;

			// submit form
			login(form.value).catch(error => {

				errors.value = error.response?.data?.errors;

				// not authorized
				if(error.response?.status == 403) { errors.value = [error.response?.data?.message]; }

				loginBtn.value.setLoading(false);
				scrollToErrors();
			});
		}


		/////////////////////////////////
		// SSO
		/////////////////////////////////

		if(route.query?.error == 'sso_mismatch') {

			errors.value.sso = [t('sso.mismatch')];
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
				"sso.mismatch": "Die E-Mail-Adresse ist bereits registriert oder einem anderen Dienst zugeordnet.",
			},
			"en": {
				"E-Mail / Benutzername": "Email / Username",
				"Passwort vergessen": "Forgot password",
				"sso.mismatch": "The email address is already registered or assigned to another service.",
			}
		}
	</i18n>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CSS
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<style lang="scss">

		.btn-login {

			min-width: 120px;
		}


	</style>
