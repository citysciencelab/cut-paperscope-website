<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div class="user-edit">

			<h3 class="content model-edit-name">{{ t("Benutzer") }} {{ t(form.id?'bearbeiten':'erstellen') }}</h3>
			<form-errors :errors="errors"/>

			<!-- PERSONAL -->
			<model-accordion label="Allgemein">
				<user-image-upload class="col-100" v-if="target.id" :target="target"/>
				<input-text label="E-Mail" id="email" class="col-50" v-model="form" :error="errors" required :readonly="!isNewModel"/>
				<input-text label="Benutzername" info="min. 3 Zeichen" id="username" class="col-50" v-model="form" required/>
				<input-text label="Name" id="name" class="col-50" v-model="form" :error="errors" required/>
				<input-text label="Nachname" id="surname" class="col-50" v-model="form" :error="errors" required/>
				<input-select label="Geschlecht" id="gender" class="col-50" :options="itemsGender" v-model="form" :error="errors" required/>
			</model-accordion>

			<!-- ADDRESS -->
			<model-accordion label="Adresse">
				<input-text label="Straße" id="street" class="col-67" v-model="form" :error="errors"/>
				<input-text label="Haus-Nr." id="street-number" class="col-33-67" v-model="form" :error="errors"/>
				<input-text label="PLZ" id="zipcode" class="col-33-67" v-model="form" :error="errors"/>
				<input-text label="Stadt" id="city" class="col-67" v-model="form" :error="errors"/>
				<input-select label="Land" id="country" class="col-50" :options="CountryCodes.de" v-model="form" :error="errors"/>
			</model-accordion>

			<!-- PASSWORD -->
			<model-accordion label="Passwort">
				<input-select label="Account Typ" id="sso_driver" :options="itemsAccountType" v-model="form" :error="errors"/>
				<input-text type="password" label="Neues Passwort" info="min. 8 Zeichen" id="password" class="col-50" v-model="form" :error="errors" :required="isNewModel" :readonly="!!form.sso_driver"/>
				<input-text type="password" label="Passwort wiederholen" id="password_confirmation" class="col-50" v-model="form" :error="errors" :required="isNewModel" :readonly="!!form.sso_driver"/>
			</model-accordion>

			<!-- CRM -->
			<model-accordion v-if="userIsAdmin" :label="t('Rechte')">
				<input-radio v-if="itemsRole.length" label="Benutzerrolle" id="role" :options="itemsRole" v-model="form" :error="errors" required/>
				<input-radio class="col-50" :label="t('Benutzer überprüft')" id="approved" :options="itemsBoolean" v-model="form" :error="errors" required/>
				<input-radio class="col-50" :label="t('Benutzer blockiert')" id="blocked" :options="itemsBoolean" v-model="form" :error="errors" required/>
			</model-accordion>

			<!--- BUTTONS -->
			<div class="form-row-buttons">
				<btn label="Abbrechen" class="secondary" to="backend.user"/>
				<btn ref="submitBtn" label="Speichern" @click="submit" blocking/>
			</div>
			<div class="form-row-buttons" style="text-align:left" v-if="!isNewModel && target?.username!='admin'">
				<btn :label="t('Account löschen')" class="secondary small" @click="confirmDelete"/>
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

		import { ref, useTemplateRef } from 'vue';
		import { useRouter } from 'vue-router';
		import { useAuth } from '@global/composables/useAuth';
		import { useApi } from '@global/composables/useApi';
		import { useForm } from '@global/composables/useForm';
		import { useUser } from '@global/composables/useUser';
		import { useLanguage } from '@global/composables/useLanguage';

		import CountryCodes from '@global/data/CountryCodes.json';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({

			target: 	{ type: Object, default: null },
		});

		const router = useRouter();
		const { deleteUser } = useAuth();
		const { form, errors, itemsBoolean, itemsGender, submitBtn, submitForm } = useForm();
		const { t } = useLanguage();

		form.value = props.target;


		/////////////////////////////////
		// FORM
		/////////////////////////////////

		const isNewModel = !form.value.id;

		function submit() {

			submitForm("user.save", data => {
				router.push({name:'backend.user'});
			});
		}


		/////////////////////////////////
		// ROLES
		/////////////////////////////////

		const { apiGet } = useApi();
		const { userIsAdmin } = useUser();
		const itemsRole = ref([]);

		apiGet('backend.user.roles',data => {
			data.forEach( i => {
				let obj = {};
				let label =  i.name.charAt(0).toUpperCase() + i.name.slice(1);
				obj[label] = i.name;
				itemsRole.value.push(obj);
			});
		});


		/////////////////////////////////
		// ACCOUNT TYPE
		/////////////////////////////////

		const itemsAccountType = [
			{ "Standard Login": null },
			{ "SSO Google": 'google' },
			{ "SSO Facebook": 'facebook' },
			{ "SSO Apple": 'apple' },
		];


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
				"delete.copy": "Möchtest du diesen Account wirklich löschen? Alle Daten werden dabei sofort und unwiderruflich gelöscht.",
			},
			"en": {
				"Altes Passwort": "Old password",
				"Neues Passwort": "New password",
				"Rechte": "Access control",
				"Benutzer überprüft": "User checked",
				"Benutzer blockiert": "User blocked",

				"Account löschen": "Delete account",
				"delete.copy": "Do you really want to delete this account? All data will be deleted immediately and irrevocably.",
			}
		}
	</i18n>
