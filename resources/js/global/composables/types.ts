/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	GLOBAL
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	declare global {

		interface Window {

			config: any;
			axios: any;
			Pusher: any;
			Echo: any;

			// gsap
			gsap: any;

			// umbrellajs
			u(selector:string): {

				nodes: HTMLElement[];

				first(): HTMLElement;
				last(): HTMLElement;
				outerHeight(): number;
				outerWidth(): number;

				addClass(className: string): void;
				removeClass(className: string): void;
				hasClass(className: string): boolean;
				toggleClass(className: string): void;
				attr(attribute: string, value?: any): void;
			};

			// lazyload
			lazyload: {
				update: () => void;
			};
			lazyloadTimer: any;
		}
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	API
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	export type ApiResponse = {

		status: number;

		data: {
			readonly status: 'success';
			readonly data?: any;
			readonly paginator?: boolean;
			readonly currentPage?: number;
			readonly pages?: number;
		};
	};


	export class ApiError extends Error {

		response: any;
		name: 'ApiError';

		constructor(message?: string, response?: any) {

			super(message);
			this.response = response;

			// Set the prototype explicitly to maintain the correct prototype chain
			Object.setPrototypeOf(this, ApiError.prototype);
		}
	}



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */

