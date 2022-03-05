import {Component} from "react";
import AjaxInterface from "./ajax-interface";
import axios, {AxiosError, AxiosResponse, Method} from 'axios';

export default class Ajax implements AjaxInterface {


    private params: Object = {};

    private route: string = '';

    doAjaxCall(method: Method, successCallBack: Function, errorCallBack: Function): void {
        axios({
            method: method,
            url: '/api/' + this.route,
            data: this.params,
        }).then((result: AxiosResponse) => {
            return successCallBack(result);
        }).catch((err: AxiosError) => {
            return errorCallBack(err);
        });
    }

    setParameters(params: Object): AjaxInterface {
        this.params = params;

        return this;
    }

    setRoute(route: string): AjaxInterface {
        this.route = route;

        return this;
    }

}
