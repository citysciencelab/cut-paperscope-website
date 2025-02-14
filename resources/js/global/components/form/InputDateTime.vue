<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>

		<div :class="['form-row', rowId]">

			<input-label :label="$attrs.label" :info="$attrs.info" v-bind="labelAttrs"/>

			<!-- INPUT -->
			<input
				:name="id"
				:id="inputId"
				v-model="value"
				v-bind="inputAttrs"
				@click="openSelector"
				@keydown.enter="openSelector"
			/>

			<!-- SELECTOR -->
			<popup ref="selector" class="date-selector" :aria-label="t('Fenster zum Editieren des Datums und der Zeitangabe')">

				<!-- HEADER -->
				<div class="date-header">
					<button class="date-header-btn" @click="prevMonth" :aria-label="t('Vorherigen Monat anzeigen')">
						<svg-item icon="backend/input-date"/>
					</button>
					<p>{{ currentMonthLabel }}</p>
					<button class="date-header-btn" @click="nexMonth" :aria-label="t('Nächsten Monat anzeigen')">
						<svg-item icon="backend/input-date"/>
					</button>
				</div>

				<!-- CALENDAR -->
				<div ref="calendar" class="date-calendar">
					<div class="date-calendar-weekday" aria-hidden="true">{{ t('monday') }}</div>
					<div class="date-calendar-weekday" aria-hidden="true">{{ t('tuesday') }}</div>
					<div class="date-calendar-weekday" aria-hidden="true">{{ t('wednesday') }}</div>
					<div class="date-calendar-weekday" aria-hidden="true">{{ t('thursday') }}</div>
					<div class="date-calendar-weekday" aria-hidden="true">{{ t('friday') }}</div>
					<div class="date-calendar-weekday" aria-hidden="true">{{ t('saturday') }}</div>
					<div class="date-calendar-weekday" aria-hidden="true">{{ t('sunday') }}</div>
					<button
						:class="['date-calendar-day',{'current':d.current}, {'active':d.timestamp==currentValueTimestamp},{today:isToday(d.timestamp)}]"
						:key="d.timestamp"
						v-for="d in days"
						@click="toggleDay($event,d)"
						:aria-label="d.label+'. '+currentMonthLabel"
					>
						{{ d.label }}
					</button>
				</div>

				<!-- TIME -->
				<div class="date-selector-time">
					<button class="date-selector-reset" @click="reset" :aria-label="t('Aktuelle Auswahl löschen')">
						<svg-item icon="backend/datalist-delete"/>
					</button>
					<input-time class="input-date-time" label="Uhrzeit" :id="id+'-time'" v-model="selectedTime"/>
				</div>

				<!-- BUTTONS -->
				<div class="form-row-buttons">
					<btn class="small secondary date-selector-cancel" label="Abbrechen" @click="selector.close()"/>
					<btn class="small date-selector-confirm" label="Bestätigen" @click="confirm"/>
				</div>
			</popup>

		</div>

	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { computed, ref, useTemplateRef } from 'vue';
		import { useInput } from '@global/composables/useInput';
		import { useLanguage } from '@global/composables/useLanguage';
		import { useDate } from '@global/composables/useDate';


		/*
		*	Usage:
		*	<input-text label="Click" id="username" v-model="myVar"/>
		*/


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const props = defineProps({

			modelValue:		{ type: [String, Number, Object] },		// bind variable to v-model
			error: 			{ default: null },						// form data to show error

			id:				{ type: String, default: null }, 		// unique form id for this input element
			placeholder: 	{ type: String },						// show a placeholder text input element
			multilang: 		{ type: Boolean, default: false },		// same input for all languages

			// html
			required: 		{ type: Boolean },						// html required attribute (show asterisk)
		});

		const emit = defineEmits(['update:modelValue']);

		const { modelIsObject, value, rowId, inputId, propId, showError, updateInput, removeError, labelAttrs } = useInput(props, emit);


		/////////////////////////////////
		// ATTRIBUTES
		/////////////////////////////////

		const inputAttrs = computed(() => ({

			type: 			props.type,
			class:			['input-date',{'error':showError.value}],

			placeholder: 	props.placeholder ? t(props.placeholder) + (props.required ? ' *' : '') : null,
			readonly: 		true,
		}));


		/////////////////////////////////
		// MULTILANG
		/////////////////////////////////

		const { t } = useLanguage();


		/////////////////////////////////
		// SELECTOR
		/////////////////////////////////

		const selector = useTemplateRef('selector');

		const currentValueDate = computed(() => value.value?.split(" ")[0] ?? null);
		const currentValueTime = computed(() => value.value?.includes(" ") ? value.value.split(" ")[1] : null);
		const currentValueTimestamp = ref(null);

		function openSelector() {

			// set calendar
			if(currentValueDate.value) {
				const d = currentValueDate.value.split(".");
				const date = new Date(parseInt(d[2]),parseInt(d[1])-1,parseInt(d[0]));
				currentMonth.value = date.getMonth();
				currentYear.value = date.getFullYear();
				currentValueTimestamp.value = date.getTime();
				selectedDate.value = date.getTime();
				selectedTime.value = currentValueTime.value;
			}
			else {
				const date = new Date();
				currentMonth.value = date.getMonth();
				currentYear.value = date.getFullYear();
				currentValueTimestamp.value = null;
				selectedTime.value = date.getHours() < 10 ? '0'+date.getHours() : date.getHours();
				selectedTime.value += ':' + (date.getMinutes() < 10 ? '0'+date.getMinutes() : date.getMinutes());
			}

			setCalendar();
			removeError();
			selector.value.open();
		}


		/////////////////////////////////
		// CALENDAR
		/////////////////////////////////

		const calendar = useTemplateRef('calendar');
		const days = ref([]);

		var currentMonth = ref(null);
		var currentYear = ref(null);
		const currentMonthLabel = computed(() => t('month.'+(currentMonth.value+1)) + ' ' + currentYear.value);

		function setCalendar() {

			days.value = [];

			// set month
			const month = currentMonth.value;
			const year = currentYear.value;

			// get day of the week starting with monday
			var firstDay = new Date(year, month, 1).getDay() - 1;
			if (firstDay < 0) firstDay = 6;

			// start list of days with previous month if not monday
			if(firstDay > 0) {

				var prevM= month - 1;
				var prevY = year;
				if(prevM < 0) { prevM = 11; prevY = year - 1;}

				var prevMonthDays = new Date(year, month, 0).getDate();
				for(var i = prevMonthDays - firstDay + 1; i <= prevMonthDays; i++) {
					days.value.push(getCalendarItem(new Date(prevY, prevM, i),false));
				}
			}

			// add days of current month
			var currentMonthDays = new Date(year, month + 1, 0).getDate();
			for(var i = 1; i <= currentMonthDays; i++) {
				days.value.push(getCalendarItem(new Date(year, month, i)));
			}

			// add days of next month till sunday
			var nextMonthDays = 7 - (days.value.length % 7);
			if(nextMonthDays < 7) {
				for(var i = 1; i <= nextMonthDays; i++) {
					days.value.push(getCalendarItem(new Date(year, month + 1, i),false));
				}
			}
		}


		function getCalendarItem(date, current=true) {

			return {
				label: date.getDate(),
				timestamp: date.getTime(),
				current,
			}
		}


		function toggleDay(e,item) {

			const isActive = u(e.target).hasClass('active');
			u(calendar.value).removeClass('error');

			// set active state in calendar
			u(calendar.value).find('.date-calendar-day').removeClass('active');
			if(!isActive) u(e.target).addClass('active');

			// set selected date
			if(!isActive) { selectedDate.value = item.timestamp; }
			else { selectedDate.value = undefined; }
		}


		function isToday(timestamp) {

			const today = (new Date()).setHours(0,0,0,0);
			return timestamp == today;
		}


		function prevMonth() {

			currentMonth.value--;
			if(currentMonth.value < 0) { currentMonth.value = 11; currentYear.value--;}
			setCalendar();
		}


		function nexMonth() {

			currentMonth.value++;
			if(currentMonth.value > 11) { currentMonth.value = 0; currentYear.value++;}
			setCalendar();
		}


		/////////////////////////////////
		// VALUE
		/////////////////////////////////

		const selectedDate = ref(undefined);
		const selectedTime	= ref(undefined);

		const { formatTime } = useDate();

		function confirm() {

			var val = '';
			var hasError = false;

			// set date value
			if(props.required && !selectedDate.value) {
				u(calendar.value).addClass('error');
				hasError = true;
			}
			else if(selectedDate.value) {
				const date = new Date(selectedDate.value);
				val = date.getDate() + '.' + (date.getMonth()+1) + '.' + date.getFullYear();
			}

			if(props.required && !selectedTime.value) {
				u(calendar.value).parent().find('.input-date-time input').addClass('error');
				hasError = true;
			}
			// set time value
			else if(selectedTime.value) {
				selectedTime.value = formatTime(selectedTime.value);
				val += ' ' + selectedTime.value;
			}

			if(hasError) { return; }

			// update model
			value.value = val;
			updateInput();

			selector.value.close();
		}


		function reset() {

			selectedDate.value = undefined;
			selectedTime.value = undefined;
			u(calendar.value).find('.date-calendar-day').removeClass('active');
			u(calendar.value).parent().find('.input-date-time input').removeClass('error');
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
				"monday": "Mo",
				"tuesday": "Di",
				"wednesday": "Mi",
				"thursday": "Do",
				"friday": "Fr",
				"saturday": "Sa",
				"sunday": "So",
			},
			"en": {
				"monday": "Mo",
				"tuesday": "Tu",
				"wednesday": "We",
				"thursday": "Th",
				"friday": "Fr",
				"saturday": "Sa",
				"sunday": "Su",

				"Fenster zum Editieren des Datums und der Zeitangabe": "Popup for editing date and time input",
				"Aktuelle Auswahl löschen": "Delete current selection",
			}
		}
	</i18n>



