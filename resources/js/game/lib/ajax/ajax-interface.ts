import { Component } from "react";
import { Method } from "axios";

export default interface AjaxInterface {
    /**
     * Set the route.
     *
     * You can leave off the /api/ aspect. the rest of the url will be used.
     *
     * @param route
     * @type [{route: string}]
     */
    setRoute(route: string): AjaxInterface;

    /**
     * Sets the parameters for the  request.
     *
     * @param params
     * @type [{params: Object}]
     */
    setParameters(params: Object): AjaxInterface;

    /**
     * Do the ajax call.
     *
     * If you stated yes to the setReturnErrorResponse and/or setReturnSuccessResponse
     * you need to pass the appropriate call back.
     *
     * @param method
     * @param successCallBack
     * @param errorCallBack
     * @type [{method: Method, successCallBack?: Function, errorCallBack?: Function}]
     */
    doAjaxCall(
        method: Method,
        successCallBack?: Function,
        errorCallBack?: Function,
    ): void;
}
