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

		//config.headers['Authorization'] = 'Bearer eyJhbGciOiJSUzI1NiIsInR5cCIgOiAiSldUIiwia2lkIiA6ICJGT0J6Ym13SzA3bDRtYl9GLUNNNGNxN3gyaHhORUdEd3MwTENoZ1lhdHpNIn0.eyJleHAiOjE3NTA3NjY5NTIsImlhdCI6MTc1MDc0ODk1MiwiYXV0aF90aW1lIjoxNzUwNzQ4OTUyLCJqdGkiOiI4MzE0M2NhMi00Mzk3LTQyZmUtYTUzOC1hOTBlZGVlMzhkM2QiLCJpc3MiOiJodHRwczovL2F1dGguY29tb2RlbGluZy5jaXR5L3JlYWxtcy9VcmJhbk1vZGVsUGxhdGZvcm0iLCJhdWQiOiJhY2NvdW50Iiwic3ViIjoiNzM3ZjZmY2ItODAxMC00NjA4LTg4NGUtNTNiYmFmOTY4MDNjIiwidHlwIjoiQmVhcmVyIiwiYXpwIjoidW1wLWNsaWVudCIsInNpZCI6IjYwMDA4ZTY0LTg3YTUtNGI4ZS1hZTJkLWFjNGVlM2QwNGU5MSIsImFjciI6IjEiLCJhbGxvd2VkLW9yaWdpbnMiOlsiaHR0cHM6Ly9zY2VuYXJpb2V4cGxvcmVyLmNvbW9kZWxpbmcuY2l0eSIsImh0dHBzOi8vY29tb2RlbGluZy5jaXR5IiwiKiIsImh0dHA6Ly9sb2NhbGhvc3QvKiIsImh0dHBzOi8vbW9kZWxwbGF0Zm9ybS5jb21vZGVsaW5nLmNpdHkvKiJdLCJyZWFsbV9hY2Nlc3MiOnsicm9sZXMiOlsiZGVmYXVsdC1yb2xlcy11cmJhbm1vZGVscGxhdGZvcm0iLCJvZmZsaW5lX2FjY2VzcyIsInVtYV9hdXRob3JpemF0aW9uIl19LCJyZXNvdXJjZV9hY2Nlc3MiOnsidW1wLWNsaWVudCI6eyJyb2xlcyI6WyJtb2RlbHNlcnZlciIsIm1vZGVscGxhdHRmb3JtIl19LCJhY2NvdW50Ijp7InJvbGVzIjpbIm1hbmFnZS1hY2NvdW50IiwibWFuYWdlLWFjY291bnQtbGlua3MiLCJ2aWV3LXByb2ZpbGUiXX19LCJzY29wZSI6Im9wZW5pZCBwcm9maWxlIGVtYWlsIiwiZW1haWxfdmVyaWZpZWQiOnRydWUsIm5hbWUiOiJ0ZXN0IHRlc3QiLCJwcmVmZXJyZWRfdXNlcm5hbWUiOiJ0ZXN0IiwiZ2l2ZW5fbmFtZSI6InRlc3QiLCJmYW1pbHlfbmFtZSI6InRlc3QiLCJlbWFpbCI6InRlc3RAdGVzdC5kZSJ9.d7BAlK-apKWuXMljd7DqSXQcVRLohzqkI1gGYQZYKe4VDWHFTC600GfgG7n_Cx8dwE3q1rktZX_HhuqKtpzW8RjWZgeSjEluj4Zm-svzQ5hDKKka1A7cFSqBH9i6TDqAqzupELIMgGUFuWIYSMkilwxkbIRhV_OoQEAMXUMPb65N418driJgqjUm_k232Mi3ZnpeDVZGCuqoSJazoNh3LWUNuMUarEbTBoLL2vo_4TLOFdHAnfdiu8109pcfXPHQiKgpolGq7QKldPWorZrvrL3RqPpdfjIa5AeosuhbDrD8MCGtfQFvoemmEXUKWT9VUQS1yk8QHgtX_278Ihcr6Q';

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
