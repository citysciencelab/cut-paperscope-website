<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<Teleport v-if="showCookieConsent" to="body">
			<popup :class="['cookie-consent',{'simple':isSimpleConsent}]" auto-open no-close aria-modal="true" :aria-label="t('Verwendung von Cookies bestätigen')">

				<!-- COPY -->
				<h2 v-if="!isSimpleConsent">{{ t("Deine Cookie-Einstellungen") }}</h2>
				<p>
					{{ t(isSimpleConsent ? "copy_simple": "copy") }}
					<router-link :to="link('page.detail',{slug:'datenschutz'})">{{ t("Datenschutzbestimmungen") }}</router-link>.
				</p>

				<!-- ESSENTIAL -->
				<div v-if="!isSimpleConsent" class="input-checkbox cookie-consent-category essential">
					<input type="checkbox" id="cookie-consent-essential" checked disabled/>
					<label for="cookie-consent-essential">
						<h3>{{ t('essential_title') }}</h3>
						<p class="small">{{ t('essential_copy') }}</p>
					</label>
					<div class="input-checkbox-icon" role="presentation">
						<svg-item icon="app/input-checkbox" />
					</div>
				</div>

				<!-- CATEGORIES -->
				<input-checkbox class="cookie-consent-category" v-if="!isSimpleConsent" v-model="selectedCategores" :options="cookieCategories" v-slot="slotProps">
					<h3>{{ t(slotProps.value+'_title') }}</h3>
					<p class="small">{{ t(slotProps.value+'_copy') }}</p>
				</input-checkbox>

				<!-- BUTTONS -->
				<template #buttons>
					<btn :label="t(isSimpleConsent ? 'Abbrechen' : 'Auswahl akzeptieren')" @click="confirmSelection" class="small"/>
					<btn :label="t(isSimpleConsent ? 'Bestätigen' : 'Alle akzeptieren')" @click="confirmAll" class="small cta"/>
				</template>

			</popup>
		</Teleport>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { ref, computed, watch, onMounted, nextTick } from 'vue';
		import { useRoute } from 'vue-router';
		import { useGlobalStore } from '@global/stores/GlobalStore';
		import { useConfig } from '@global/composables/useConfig';
		import { useLanguage } from '@global/composables/useLanguage';
		import Cookies from 'js-cookie';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const route = useRoute();
		const store = useGlobalStore();
		const { isLocal } = useConfig();
		const { t } = useLanguage();

		const isCookieEnabled = ref(!isLocal || window.config.cookie_enabled);
		const isPrivacyPage = computed(() => route.name == 'page.detail' && route.params.slug == 'datenschutz');
		const isSimpleConsent = ref(window.config.cookie_categories.length==1);
		const showCookieConsent = computed(()=>isCookieEnabled.value && !store.usedCookieConsent && !isPrivacyPage.value);

		const cookieCategories = ref(window.config.cookie_categories);
		const selectedCategores = ref([]);


		/////////////////////////////////
		// USED COOKIE CONSENT
		/////////////////////////////////

		const cookieConsent = ref({});

		updateCookieConsent();

		function updateCookieConsent() {

			window.config.cookie_categories.forEach( i => {

				let cookie = Cookies.get(window.config.cookie_name+'_'+i);
				if(cookie) { cookieConsent.value[i] = cookie == '1'; }
				else { cookieConsent.value[i] = undefined; }
			});

			store.setCookieConsent(cookieConsent.value);
		}


		/////////////////////////////////
		// CONFIRM
		/////////////////////////////////

		const cookieParams = {
			expires: window.config.cookie_expires,
			domain: window.config.cookie_domain,
			path: window.config.base_path,
		};


		function confirmSelection() {

			cookieCategories.value.forEach( i => {
				Cookies.set(window.config.cookie_name+'_'+i, selectedCategores.value.includes(i) ? 1 : -1, cookieParams);
			});


			updateCookieConsent();
		}


		function confirmAll() {

			cookieCategories.value.forEach( i => {
				Cookies.set(window.config.cookie_name+'_'+i, 1, cookieParams);
			});

			updateCookieConsent();
		}


		/////////////////////////////////
		// ACCESSIBILITY
		/////////////////////////////////

		function setFocus() {

			var target = null;
			if(store.usedCookieConsent) { target = document.body; }
			else if(showCookieConsent.value && isSimpleConsent.value) { target = u('.cookie-consent p').first(); }
			else if(showCookieConsent.value) { target = u('.cookie-consent h2').first();  }

			if(target && target != document.activeElement) {
				target.setAttribute("tabindex", "-1");
				target.focus();
				target.removeAttribute("tabindex");
			}
		}

		onMounted(()=>setTimeout(setFocus, 250));
		watch(() => store.usedCookieConsent, setFocus);


</script>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    LANG
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<i18n lang="json5">
		{
			"de": {
				"copy_simple": "Diese Seite verwendet Statistik-Cookies um das Nutzererlebnis zu steigern. Weitere Informationen in unseren ",
				"copy": "Diese Seite verwendet essenzielle und optionale Cookies, deren Nutzung Du individuell anpassen kannst. Infos dazu findest Du in den",
				"essential_title": "Essenziell",
				"essential_copy": "Essenzielle Cookies sind notwendig für die volle Funktion der Website. Ohne sie funktioniert sie nicht richtig.",
				"analytics_title": "Web-Statistiken",
				"analytics_copy": "Statistik-Cookies ermöglichen uns, anonym das Nutzerverhalten zu analysieren und unser Angebot zu verbessern.",
				"marketing_title": "Marketing",
				"marketing_copy": "Marketing-Cookies helfen uns, Interessen zu verstehen und gezielt anonym Werbung zu zeigen.",
			},
			"en": {
				"Verwendung von Cookies bestätigen": "Accept the use of cookies",
				"Deine Cookie-Einstellungen": "Your cookie settings",

				"copy_simple": "This site uses cookies for web analytics to optimize your user experience. More information in our ",
				"copy": "This site uses essential and optional cookies, which you can customize. More information can be found in the",
				"essential_title": "Essential",
				"essential_copy": "Essential cookies are mandatory for the full functionality of the website. Without them, it won't function properly.",
				"analytics_title": "Web Analytics",
				"analytics_copy": "Web analytics cookies allow us to analyze user behavior anonymously and improve our services.",
				"marketing_title": "Marketing",
				"marketing_copy": "Marketing cookies help us understand user interests and show targeted ads anonymously.",
				"Auswahl akzeptieren": "Accept selection",
				"Alle akzeptieren": "Accept all",
			}
		}
	</i18n>
