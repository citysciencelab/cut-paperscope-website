<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    HTML
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<template>


			<div class="project-navi">

				<scroller>
					<div class="project-navi-content">
						<slot></slot>
					</div>
				</scroller>

				<user-image link-profile/>
				<btn label="Logout" class="small project-navi-logout" icon="btn-logout" @click="logout"/>

			</div>


	</template>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    JAVASCRIPT
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<script setup>

		import { useLanguage } from '@global/composables/useLanguage';
		import { useAuth } from '@global/composables/useAuth';


		/////////////////////////////////
		// INIT
		/////////////////////////////////

		const { t } = useLanguage();
		const { logout } = useAuth();


	</script>



<!--/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//    LANG
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////-->


	<i18n lang="json5">
		{
			"de": {
			},
			"en": {
			}
		}
	</i18n>

