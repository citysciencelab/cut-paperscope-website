/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// vue
	import { storeToRefs } from 'pinia'

	// app
	import { useContentStore } from '@app/stores/ContentStore';


/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	COMPOSABLE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


export const useUmp = () => {


	/////////////////////////////////
	// REQUEST
	/////////////////////////////////

	const umpBaseUrl: string = 'https://modelplatform.comodeling.city/api/';
	const umpAuthUrl: string = "https://auth.comodeling.city/realms/UrbanModelPlatform/protocol/openid-connect/";

	const umpRequest = window.axios.create();
	const { umpBearerToken } = storeToRefs(useContentStore());

	umpRequest.interceptors.request.use((config:any) => {

		delete config.headers['X-Requested-With'];
		delete config.headers['X-Context'];

		if(umpBearerToken?.value) {
			config.headers['Authorization'] = 'Bearer ' + umpBearerToken.value;
		}

		return config;
	});

	umpRequest.interceptors.response.use((r:any) => r, (e:any) => onError(e));


	function onError(error:any) {

		// 401: Unauthorized
		if (error.response && error.response.status == 401) {
			umpBearerToken.value = null;
			localStorage.removeItem('ump_bearer_token');
		}

		throw error;
	}


	/////////////////////////////////
	// PROCESSES
	/////////////////////////////////

	function getProcesses() {

		const config:any = {
			headers: { 'Authorization': 'Bearer ' + umpBearerToken.value }
		};

		return umpRequest.get(umpBaseUrl+'processes/',config).then((response:any) => {

			const processes = response.data.processes;
			processes.forEach(p => p.info = p.links.find(l => l.rel == "alternate")?.href );

			return processes;
		})
		.catch(onError);
	}


	function getProcess(id: string) {

		return umpRequest.get(umpBaseUrl+'processes/'+id).then((response:any) => {

			return response.data;
		})
		.catch(onError);
	}


	function executeProcess(project: any, processForm: any) {

		// reshape form data to match API requirements
		const data = {
			job_name : processForm.job_name || 'PaperScope Simulation',
			inputs: processForm,
			bbox: [
				project.start_longitude,
				project.start_latitude,
				project.end_longitude,
				project.end_latitude
			]
		}
		data.inputs.paperscope_project = project.id;

		return umpRequest.post(umpBaseUrl+'processes/'+processForm.id+'/execution', data)
		.catch(onError);
	}


	/////////////////////////////////
	// JOBS
	/////////////////////////////////

	function getJobs(projectId: string, processId: string) {

		return umpRequest.get(umpBaseUrl+'jobs/').then((response:any) => {

			const jobs = response.data.jobs.filter(j => j.processID == processId && j.parameters.inputs.paperscope_project == projectId);
			return jobs;
		})
		.catch(onError);
	}


	/////////////////////////////////
	// EXPORT
	/////////////////////////////////

	return {
		umpBaseUrl, umpAuthUrl,
		umpRequest,
		getProcesses, getProcess, executeProcess,
		getJobs,
	};



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */



};
