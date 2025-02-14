<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<section class="content auth" v-if="!isRedirect">

			<h1>{{ t("title") }}</h1>

			<!-- FORM -->
			<form-errors :errors="errors"/>
			<input-text label="E-Mail" id="email-register" v-model="form.email" :error="errors.email" :max-length="100" autofocus required/>
			<input-text label="Benutzername" id="username" info="min. 3 Zeichen" v-model="form" :error="errors" :max-length="30" autofocus required/>
			<input-text label="Name" id="name" v-model="form" :error="errors" :max-length="50" required/>
			<input-text label="Nachname" id="surname" v-model="form" :error="errors" :max-length="50" required/>
			<input-select label="Geschlecht" id="gender" :options="itemsGender" v-model="form" :error="errors" required/>
			<input-text label="Passwort" info="min. 8 Zeichen" id="password-register" @enter="submit" v-model="form.password" :error="errors.password" type="password" required/>
			<input-text label="Passwort wiederholen" id="password-confirmation" v-model="form" :error="errors" type="password" required/>

			<input-checkbox id="newsletter" :options="itemsNewsletter" v-model="form" :error="errors"/>
			<input-checkbox id="terms" :options="[{'terms_label':true}]" v-model="form" :error="errors">
				{{ t('terms_label') }} <router-link :to="link('page.detail',{slug:'datenschutz'})" target="_blank" class="textlink">{{ t('terms_link') }}</router-link>
			</input-checkbox>

			<!-- BUTTONS -->
			<div class="form-row-buttons">
				<btn ref="registerBtn" class="btn-register" :label="t('Registrieren')" @click="submit" blocking/>
			</div>

			<sso-buttons/>

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
		import { useLanguage } from '@global/composables/useLanguage';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const { form, errors, itemsGender, scrollToErrors } = useForm();
		const { register } = useAuth();
		const { t, redirectIfUser } = usePage("Registrieren");
		const { activeLang } = useLanguage();

		const isRedirect = ref( redirectIfUser() );


		/////////////////////////////////
		// FORM
		/////////////////////////////////

		const registerBtn = useTemplateRef('registerBtn');
		const itemsNewsletter = ref([{'Newsletter abonnieren':true}]);

		function submit() {

			form.value.lang = activeLang.value;

			// submit form
			register(form.value).catch(e => {
				errors.value = e.response?.data?.errors;
				registerBtn.value.setLoading(false);
				scrollToErrors();
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
				"title": "Registrierung",
				"terms_label": "Mit der Anmeldung bestätige ich die",
				"terms_link": "Datenschutzbestimmungen",
			},
			"en": {
				"title": "Registration",
				"terms_label": "With the registration I confirm the",
				"terms_link": "privacy policy",
			}
		}
	</i18n>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    CSS
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<style lang="scss">

		.btn-register {

			min-width: 120px;
		}


	</style>
