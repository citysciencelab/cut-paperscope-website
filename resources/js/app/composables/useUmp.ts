/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// vue



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	COMPOSABLE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


export const useUmp = () => {


	/////////////////////////////////
	// REQUEST
	/////////////////////////////////

	const umpBaseUrl = 'https://modelplatform.comodeling.city/api/';
	const umpRequest = window.axios.create();

	umpRequest.interceptors.request.use((config:any) => {

		delete config.headers['X-Requested-With'];
		delete config.headers['X-Context'];

		//config.headers['Authorization'] = '';

		return config;
	});


	function onError(error:any) {

		throw error;
	}


	/////////////////////////////////
	// PROCESSES
	/////////////////////////////////

	function getProcesses() {

		return umpRequest.get(umpBaseUrl+'processes/').then((response:any) => {

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
		umpRequest,
		getProcesses, getProcess, executeProcess,
		getJobs,
	};



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */



};
