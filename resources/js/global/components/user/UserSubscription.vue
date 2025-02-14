<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div class="user-subscription">

			<!-- LOADING -->
			<div v-if="isLoading" class="user-subscription-loading">
				<loading-spinner/>
			</div>

			<!-- NO SUBSCRIPTION -->
			<div v-if="!subscription && !isLoading" class="user-subscription-empty">
				<h4>{{ t('Kein Abo') }}</h4>
			</div>

			<!-- INFO -->
			<div v-else-if="subscription && !isLoading" class="user-subscription-info">
				<h4>
					{{ t('Name') }}: {{ t('name.'+subscription.name.toLowerCase()) }}<br>
					{{ t('Status') }}: {{ t('status.'+subscription.status) }}
					{{ subscription.status != 'canceled' && subscription.cancel_at ? '('+t('status.terminated')+')' : null }}
				</h4>
				<p v-if="!isFree">
					{{ t('Aktueller Zeitraum: bis') }} {{ timestampToDate(subscription.current_period_end) }}<br>
					{{ t('Aktueller Preis:') }}: {{ subscription.price }}<br>
					{{ t('Abrechnung') }}: {{ t(subscription.charge_automatic ? 'automatisch bezahlen': 'Rechnung senden' ) }}<br>
				</p>
				<p v-if="subscription.cancel_at" class="user-subscription-ending">
					{{ t('Dein Abo endet am') }}: {{ timestampToDate(subscription.cancel_at) }}<br>
				</p>

				<!-- INVOICES -->
				<h4 v-if="!isFree"> {{ t('Letzte Rechnungen') }} </h4>
				<stripe-invoice v-for="invoice in subscription.invoices" :item="invoice"/>
			</div>

			<!-- BUTTONS -->
			<div v-if="subscription && !isFree" class="form-row-buttons">
				<btn ref="btnCancel" class="secondary small" v-if="!subscription.cancel_at" :label="t('Abo kündigen')" @click="confirmCancel" blocking/>
				<btn ref="btnResume" class="secondary small" v-else :label="t('Abo wieder aufnehmen')" @click="confirmResume" blocking/>
			</div>

		</div>

		<!-- POPUP CANCEL -->
		<popup-modal ref="confirmModal"/>


	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, useTemplateRef, computed } from 'vue';

		import { useRouter } from 'vue-router';
		import { useApi } from "@global/composables/useApi";
		import { useUser } from '@global/composables/useUser';
		import { useLanguage } from '@global/composables/useLanguage';
		import { useDate } from '@global/composables/useDate';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const router = useRouter();
		const { apiPost } = useApi();
		const { user } = useUser();
		const { t } = useLanguage();
		const { timestampToDate } = useDate();


		/////////////////////////////////
		// SUBSCRIPTION
		/////////////////////////////////

		const subscription = ref(null);
		const isLoading = ref(true);
		const isFree = computed(() => subscription.value && subscription.value.name == 'free');

		apiPost('stripe.subscription', {}, data => {
			subscription.value = data;
		})
		.catch(error => {

			if(error.response.status == 404) { subscription.value = false; }
			else { console.error(error); }
		})
		.finally(() => isLoading.value = false);


		/////////////////////////////////
		// CANCEL
		/////////////////////////////////

		const confirmModal = useTemplateRef('confirmModal');
		const btnCancel = useTemplateRef('btnCancel');

		function confirmCancel() {

			btnCancel.value.setLoading(false);

			confirmModal.value.open({
				title: t('Abo kündigen'),
				copy: t('cancel.copy'),
				alert: true,
				confirmLabel: t("Abo kündigen"),
				callback: cancelSubscription
			});
		}

		function cancelSubscription() {

			btnCancel.value.setLoading(true);

			const data = {
				id: user.value.id,
				type: subscription.value.name,
			}

			apiPost('stripe.subscription.cancel', data, data => subscription.value = data)
			.catch(error => console.error(error));
		}


		/////////////////////////////////
		// RESUME
		/////////////////////////////////

		const btnResume = useTemplateRef('btnResume');

		function confirmResume() {

			btnResume.value.setLoading(false);

			confirmModal.value.open({
				title: t('Abo wieder aufnehmen'),
				copy: t('resume.copy'),
				alert: true,
				confirmLabel: t("Abo wieder aufnehmen"),
				callback: resumeSubscription
			});
		}

		function resumeSubscription() {

			btnResume.value.setLoading(true);

			const data = {
				id: user.value.id,
				type: subscription.value.name,
			}

			apiPost('stripe.subscription.resume', data, data => subscription.value = data)
			.catch(error => console.error(error));
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
				"name.default": "Premium",
				"name.free": "Kostenlos",

				"status.incomplete": "unvollständig",
				"status.incomplete_expired": "unvollständig abgelaufen",
				"status.trialing": "Testphase",
				"status.active": "aktiv",
				"status.past_due": "überfällig",
				"status.canceled": "gekündigt",
				"status.unpaid": "nicht bezahlt",
				"status.paused": "pausiert",
				"status.terminated": "gekündigt",

				"cancel.copy": "Möchtest Du dein Abo wirklich kündigen?",
				"resume.copy": "Möchtest Du dein Abo wirklich wieder aufnehmen?",
			},
			"en": {
				"name.default": "Premium",
				"name.free": "Free",

				"status.incomplete": "incomplete",
				"status.incomplete_expired": "incomplete expired",
				"status.trialing": "trialing",
				"status.active": "active",
				"status.past_due": "past due",
				"status.canceled": "canceled",
				"status.unpaid": "unpaid",
				"status.paused": "paused",
				"status.terminated": "terminated",

				"Kein Abo": "No subscription",
				"Abo kündigen": "Cancel subscription",
				"Abo wieder aufnehmen": "Resume subscription",

				"Aktueller Zeitraum: bis": "Current interval: until",
				"Aktueller Preis:": "Current price",
				"Abrechnung": "Payment",
				"automatisch bezahlen": "pay automatically",
				"Rechnung senden": "send invoice",
				"Dein Abo endet am": "Your subscription ends at",
				"Letzte Rechnungen": "Last invoices",

				"cancel.copy": "Are you sure you want to cancel your subscription?",
				"resume.copy": "Are you sure you want to resume your subscription?",
			}
		}
	</i18n>
