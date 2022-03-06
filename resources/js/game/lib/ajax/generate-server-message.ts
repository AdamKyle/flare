import Ajax from "./ajax";
import {AxiosError, AxiosResponse} from "axios";

/**
 * Generate a server message for an action.
 *
 * @param type
 * @param customMessage
 * @type [{type: string, customMessage?: string}]
 */
export const generateServerMessage = (type: string, customMessage?: string) => {
    (new Ajax()).setRoute('/server-message').setParameters({
        params: {
            type: type,
            custom_message: customMessage
        }
    }).doAjaxCall('get', (result: AxiosResponse) => {}, (error: AxiosError) => {
        if (error.hasOwnProperty('response')) {
            const response = error.response;

            if (typeof response === 'undefined') {
                return;
            }

            if (response.status === 401) {
                return location.reload()
            }
        }
    });
}
