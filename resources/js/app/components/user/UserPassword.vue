<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div class="user-password">

			<form-errors :errors="errors"/>

			<!-- DEFAULT USER -->
			<div v-if="!user.sso_driver" class="form-section">
				<input-text label="Altes Passwort" id="password-old" type="password" v-model="form.old" :error="errors.old" required style="margin-bottom:2.5rem"/>
				<input-text label="Neues Passwort" id="password-new" type="password" info="min. 8 Zeichen" v-model="form.password" :error="errors.password" required/>
				<input-text label="Passwort bestätigen" type="password" id="password-new-confirmation" v-model="form.password_confirmation" :error="errors.password_confirmation" required/>
			</div>
			<!-- SSO USER -->
			<div v-else class="form-section">
				<p>{{ t('password.sso', [user.sso_driver]) }}</p>
			</div>

			<!--- BUTTONS -->
			<div v-if="!user.sso_driver" class="form-row-buttons">
				<btn label="Abbrechen" class="secondary" :to="confirmUrl"/>
				<btn ref="submitBtn" label="Speichern" @click="submit" blocking/>
			</div>
			<div v-else class="form-row-buttons">
				<btn label="Zurück" class="secondary small" to="home"/>
			</div>

		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { computed } from 'vue';
		import { useRouter } from 'vue-router';
		import { useConfig } from '@global/composables/useConfig';
		import { useUser } from '@global/composables/useUser';
		import { useForm } from '@global/composables/useForm';
		import { useLanguage } from '@global/composables/useLanguage';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const router = useRouter();
		const { t } = useLanguage();
		const { webContext } = useConfig();
		const { user } = useUser();
		const { form, errors, submitBtn, submitForm } = useForm();


		/////////////////////////////////
		// FORM
		/////////////////////////////////

		const confirmUrl = computed(() => {
			return webContext == 'app' ? 'user' : 'home';
		});

		function submit() {

			form.value.id = user.value.id;

			submitForm("user.password", data => router.push({name:confirmUrl.value}) );
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
				"password.sso": "Passwort kann nicht geändert werden, da Du Dich per SingleSignOn über den Dienst \"{0}\" angemeldet hast.",
			},
			"en": {
				"Altes Passwort": "Old password",
				"Neues Passwort": "New password",
				"Passwort bestätigen": "Confirm password",
				"password.sso": "Password cannot be changed because you are logged in via SingleSignOn using the service \"{0}\".",
			}
		}
	</i18n>
