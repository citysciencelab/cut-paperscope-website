/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// vue
	import { mount } from '@vue/test-utils';
	import { nextTick, defineComponent } from 'vue';
	import { mockedI18n } from '@tests/Vitest/Helper/Mocks/useI18nMock';
	import { mockedLanguage } from '@tests/Vitest/Helper/Mocks/useLanguageMock';

	// test composable
	import { useInput } from '@global/composables/useInput'



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	WRAPPER COMPONENT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	const component = defineComponent({

		props: {
			id: { type:String },
			modelValue: { default:undefined },
			error: { default:false },
			multilang: { type:Boolean, default:false },
			required: { type:Boolean, default:false },
		},

		emits: ['update:modelValue'],
		template: '<div></div>',

		setup (props, { emit }) {
			return { ...useInput(props,emit) }
		}
	});



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	IDS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('correct ids', () => {

		// act
		const wrapper = mount(component, {
			props: {
				id: 'test-id',
				modelValue: 'test-value',
				error: false,
			},
		});

		// assert
		expect(wrapper.vm.rowId).toBe('row-test-id');
		expect(wrapper.vm.inputId).toBe('input-test-id');
		expect(wrapper.vm.propId).toBe('test_id');
	});


	test('auto ids if missing prop', () => {

		// mock getCurrentInstance (hoisted)
		vi.mock('vue', async (importOriginal) => {
			const mod = await importOriginal()
			return {
				...mod,
				getCurrentInstance: () => { return { uid: '123' } },
			}
		});

		// act
		const wrapper = mount(component, {
			props: {
				modelValue: 'test-value',
				error: false,
			},
		});

		// assert
		expect(wrapper.vm.rowId).toBe('row-123');
		expect(wrapper.vm.inputId).toBe('input-123');
		expect(wrapper.vm.propId).toBe('val_123');
	});



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	VALUE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('correct updateInput', () => {

		// act
		const wrapper = mount(component, {
			props: {
				id: 'test',
				modelValue: 'test-value',
				error: false,
			},
		});

		// assert
		expect(wrapper.vm.value).toBe('test-value');
		expect(wrapper.vm.showError).toBe(false);

		// update value
		wrapper.vm.value = 'new-value';
		wrapper.vm.updateInput();

		// assert
		expect(wrapper.emitted("update:modelValue")).toBeTruthy();
		expect(wrapper.vm.value).toBe('new-value');
	});


	test('correct updateInput if v-model is object', () => {

		const form = { test: 'test-value' };

		// act
		const wrapper = mount(component, {
			props: {
				id: 'test',
				modelValue: form,
				error: false,
			},
		});

		// assert
		expect(wrapper.vm.value).toBe('test-value');

		// update value
		wrapper.vm.value = 'new-value';
		wrapper.vm.updateInput();

		// assert
		expect(wrapper.emitted("update:modelValue")).toContainEqual([ { test: 'new-value' } ]);
	});


	test('correct updateInput if v-model is empty object', () => {

		const form = {};

		// act
		const wrapper = mount(component, {
			props: {
				id: 'test',
				modelValue: form,
				error: false,
			},
		});

		// assert
		expect(wrapper.vm.value).toBe(null);

		// update value
		wrapper.vm.value = 'new-value';
		wrapper.vm.updateInput();

		// assert
		expect(wrapper.emitted("update:modelValue")).toContainEqual([ { test: 'new-value' } ]);
	});


	test('correct updateInput if multi lang object', () => {

		const form = {
			test_de: 'test-value-de',
			test_en: 'test-value-en',
		};

		// act
		const wrapper = mount(component, {
			props: {
				id: 'test',
				modelValue: form,
				error: false,
				multilang: true,
			},
		});

		// assert
		expect(wrapper.vm.value).toBe('test-value-de');

		// update value
		wrapper.vm.value = 'new-value';
		wrapper.vm.updateInput();

		// assert
		expect(wrapper.emitted("update:modelValue")).toBeTruthy();
		expect(wrapper.vm.value).toBe('new-value');
	});


	test('correct updateInput if empty multi lang object', () => {

		const form = {};

		// act
		const wrapper = mount(component, {
			props: {
				id: 'test',
				modelValue: form,
				error: false,
				multilang: true,
			},
		});

		// assert
		expect(wrapper.vm.value).toBe(null);

		// update value
		wrapper.vm.value = 'new-value';
		wrapper.vm.updateInput();

		// assert
		expect(wrapper.emitted("update:modelValue")).toBeTruthy();
		expect(wrapper.vm.value).toBe('new-value');
	});



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	ERROR
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('correct error', () => {

		// act
		const wrapper = mount(component, {
			props: {
				id: 'test',
				modelValue: "test-value",
				error: true,
			},
		});

		// assert
		expect(wrapper.vm.showError).toBe(true);

		// update
		wrapper.vm.removeError();

		// assert
		expect(wrapper.vm.showError).toBe(false);
	});


	test('correct error if error is object', async () => {

		// act
		const wrapper = mount(component, {
			props: {
				id: 'test',
				modelValue: {test_de: "test-value"},
				multilang: true,
				error: {
					test_de: ["error message"],
				},
			},
		});

		// assert
		expect(wrapper.vm.showError).toBe(true);

		// update
		wrapper.vm.removeError();

		// assert
		expect(wrapper.vm.showError).toBe(false);
	});


	test('correct error on error prop update', async () => {

		// act
		const wrapper = mount(component, {
			props: {
				id: 'test',
				modelValue: "test-value",
				error: true,
			},
		});

		// assert
		expect(wrapper.vm.showError).toBe(true);

		// update prop
		wrapper.setProps({ error: false });
		await nextTick();

		// assert
		expect(wrapper.vm.showError).toBe(false);
	});



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	LABEL
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	test('correct label attributes', () => {

		// act
		const wrapper = mount(component, {
			props: {
				id: 'test',
				modelValue: {},
				error: false,
				multilang: true,
				required: true,
			},
		});

		// assert
		expect(wrapper.vm.labelAttrs.id).toBe(wrapper.vm.inputId);
		expect(wrapper.vm.labelAttrs.multilang).toBe(true);
		expect(wrapper.vm.labelAttrs.error).toBe(false);
		expect(wrapper.vm.labelAttrs.required).toBe(true);
	});

