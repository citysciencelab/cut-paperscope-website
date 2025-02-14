/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// vue
	import { ref } from "vue";

	// app
	import { ApiResponse, ApiError } from '@global/composables/types';
	import { useApi } from "@global/composables/useApi";




/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	COMPOSABLE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


export const useForm = () => {

	const { createRoute } = useApi();


	/////////////////////////////////
	// INIT
	/////////////////////////////////

	const form 		= ref({});
	const errors 	= ref({});


	/////////////////////////////////
	// DEFAULT CHECKBOX/RADIO ITEMS
	/////////////////////////////////

	const itemsPublic 	= ref([ {'Öffentlich':true}, {'Nicht öffentlich':false} ]);
	const itemsShow 	= ref([ {'anzeigen':true}, {'ausblenden':false} ]);
	const itemsBoolean 	= ref([ {'Ja':true}, {'Nein':false} ]);
	const itemsGender 	= ref([ {'Weiblich':'f'}, {'Männlich':'m'}, {'Divers':'d'}, {'Keine Angabe':'u'} ]);


	/////////////////////////////////
	// SUBMIT
	/////////////////////////////////

	const submitBtn = ref(null);

	/**
	 * Submit form data to a laravel api route.
	 */

	async function submitForm(routeName: string, callback: Function): Promise<ApiResponse|ApiError> {

		return window.axios.post(createRoute(routeName),form.value)
			.then((response: ApiResponse) => {
				errors.value = {};
				callback(response.data?.data)
				return response;
			})
			.catch((error: ApiError) => {
				errors.value = error?.response?.data?.errors ?? null;
				if(errors.value) { scrollToErrors(); }
				else { console.log("useForm: error", error); }
				return error;
			})
			.finally(() => {
				if(submitBtn.value) { submitBtn.value.setLoading(false); }
			});
	};


	/////////////////////////////////
	// ERROR
	/////////////////////////////////

	function scrollToErrors() {

		setTimeout(() => {

			const firstError = window.u('.form-errors').first();
			if(!firstError) { return; }

			const rect = firstError.getBoundingClientRect();
			const header = window.u('header').outerHeight();
			const offset = firstError ? rect.top + window.scrollY - header * 1.5 : 0;

			window.scrollTo({top: offset, behavior: 'smooth'});

		}, 100);
	};


	/////////////////////////////////
	// EXPORT
	/////////////////////////////////

	return {
		form, errors,
		itemsPublic, itemsShow, itemsBoolean, itemsGender,
		submitBtn, submitForm,
		scrollToErrors,
	 };



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


};
