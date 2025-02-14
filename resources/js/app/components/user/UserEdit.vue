<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div class="user-edit">

			<form-errors :errors="errors"/>

			<!-- PERSONAL -->
			<div class="form-section cols">
				<user-image-upload class="col-100" :target="store.user"/>
				<input-text label="E-Mail" id="email" class="col-50" v-model="form" required readonly/>
				<input-text label="Benutzername" info="min. 3 Zeichen" id="username" class="col-50" v-model="form" required/>
				<input-text label="Name" id="name" class="col-50" v-model="form" :error="errors" required/>
				<input-text label="Nachname" id="surname" class="col-50" v-model="form" :error="errors" required/>
				<input-select label="Geschlecht" id="gender" class="col-50" :options="itemsGender" v-model="form" :error="errors" required/>
			</div>

			<!-- ADDRESS -->
			<div class="form-section cols">
				<input-text label="Straße" id="street" class="col-67" v-model="form" :error="errors"/>
				<input-text label="Haus-Nr." id="street-number" class="col-33-67" v-model="form" :error="errors"/>
				<input-text label="PLZ" id="zipcode" class="col-33-67" v-model="form" :error="errors"/>
				<input-text label="Stadt" id="city" class="col-67" v-model="form" :error="errors"/>
				<input-select label="Land" id="country" class="col-50" :options="CountryCodes.de" v-model="form" :error="errors"/>
			</div>

			<!--- BUTTONS -->
			<div class="form-row-buttons">
				<btn label="Abbrechen" class="secondary" :to="confirmUrl"/>
				<btn ref="submitBtn" label="Speichern" @click="submit" blocking/>
			</div>
			<div class="form-row-buttons" style="text-align:left">
				<btn :label="t('Account löschen')" class="secondary small btn-delete" @click="confirmDelete"/>
			</div>

			<popup-modal ref="deleteModal"/>

		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, computed, useTemplateRef } from 'vue';
		import { useRouter } from 'vue-router';
		import { useUserStore } from '@global/stores/UserStore';
		import { useConfig } from '@global/composables/useConfig';
		import { useAuth } from '@global/composables/useAuth';
		import { useForm } from '@global/composables/useForm';
		import { useLanguage } from '@global/composables/useLanguage';

		import CountryCodes from '@global/data/CountryCodes.json';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({

			target: 	{ type: Object, default: null },
		});

		const router = useRouter();
		const store = useUserStore();

		const { webContext } = useConfig();
		const { getUser, deleteUser } = useAuth();
		const { form, errors, itemsGender, submitBtn, submitForm } = useForm();
		const { t } = useLanguage();


		// fill form with user data
		if( props.target) {
			form.value = props.target;
		}
		else {
			form.value = JSON.parse(JSON.stringify(store.user));	// preload form with deep copy of user data
			getUser().then( user => form.value = user );
		}


		/////////////////////////////////
		// FORM
		/////////////////////////////////

		const confirmUrl = computed(() => webContext == 'app' ? 'user' : 'home');

		function submit() {

			submitForm("user.save", data => {
				store.setUser(data);
				router.push({name:confirmUrl.value});
			});
		}


		/////////////////////////////////
		// DELETE
		/////////////////////////////////

		const deleteModal = useTemplateRef('deleteModal');

		function confirmDelete() {

			deleteModal.value.open({
				title: t("Account löschen"),
				copy: t("delete.copy"),
				alert: true,
				confirmLabel: t("Account löschen"),
				callback: () => deleteUser(form.value.id)
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
				"delete.copy": "Möchtest du deinen Account wirklich löschen? Alle deine Daten werden dabei sofort und unwiderruflich gelöscht.",
			},
			"en": {
				"Account löschen": "Delete account",
				"delete.copy": "Do you really want to delete your account? All your data will be deleted immediately and irrevocably.",
			}
		}
	</i18n>

