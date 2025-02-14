 /*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// Vue
	import { createRouter } from '@global/GlobalRouter.js';

	// backend
	import PageDashboard from './pages/PageDashboard.vue';

 	// page
	const PagePageList = () => import('./pages/page/PagePageList.vue');
	const PagePageEdit = () => import('./pages/page/PagePageEdit.vue');

	// item
	const PageItemList = () => import('./pages/item/PageItemList.vue');
	const PageItemEdit = () => import('./pages/item/PageItemEdit.vue');

	// fragment
	const PageFragmentEdit = () => import('./pages/fragment/PageFragmentEdit.vue');

	// user
	const PageUserList = () => import('./pages/user/PageUserList.vue');
	const PageUserEdit = () => import('./pages/user/PageUserEdit.vue');

	// project
	const PageProjectList = () => import('./pages/project/PageProjectList.vue');
	const PageProjectEdit = () => import('./pages/project/PageProjectEdit.vue');

	// [add model includes]

	// setting
	const PageSettingList = () => import('./pages/setting/PageSettingList.vue');
	const PageSettingEdit = () => import('./pages/setting/PageSettingEdit.vue');

	// file manager
	const PageFileManager = () => import('./pages/file-manager/PageFileManager.vue');

	// feature routes
	import AuthRoutes from '@global/routes/AuthRoutes.js';
	import UserRoutes from '@app/routes/UserRoutes.js';



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	BACKEND ROUTES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	var routes = [

		{ path: 'backend', name: 'backend.index', component: PageDashboard, meta:{auth:true} },

		// page
		{ path: 'backend/page/', name: 'backend.page', component: PagePageList, meta:{auth:true} },
		{ path: 'backend/page/edit/:id?', name: 'backend.page.edit', component: PagePageEdit, meta: {auth:true} },

		// item
		{ path: 'backend/item/', name: 'backend.item', component: PageItemList, meta:{auth:true} },
		{ path: 'backend/item/edit/:id?', name: 'backend.item.edit', component: PageItemEdit, meta: {auth:true} },

		// fragment
		{ path: 'backend/fragment/:parentType/:parent/:id?', name: 'backend.fragment.edit', component: PageFragmentEdit, meta:{auth:true} },

		// user
		{ path: 'backend/user/', name: 'backend.user', component: PageUserList, meta:{auth:true} },
		{ path: 'backend/user/edit/:id?', name: 'backend.user.edit', component: PageUserEdit, meta: {auth:true} },

		// project
		{ path: 'backend/project/', name: 'backend.project', component: PageProjectList, meta:{auth:true} },
		{ path: 'backend/project/edit/:id?', name:'backend.project.edit', component: PageProjectEdit, meta: {auth:true} },

		// [add model routes]

		// setting
		{ path: 'backend/setting/', name: 'backend.setting', component: PageSettingList, meta:{auth:true} },
		{ path: 'backend/setting/edit/:id?', name: 'backend.setting.edit', component: PageSettingEdit, meta: {auth:true} },

		// file manager
		{ path: 'backend/file-manager/', name: 'backend.file-manager', component: PageFileManager, meta: {auth:true} },
	];



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	FEATURE ROUTES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	routes = routes.concat(AuthRoutes);
	routes = routes.concat(UserRoutes);



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	export default createRouter(routes);


