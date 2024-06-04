var __decorate =
    (this && this.__decorate) ||
    function (decorators, target, key, desc) {
        var c = arguments.length,
            r =
                c < 3
                    ? target
                    : desc === null
                      ? (desc = Object.getOwnPropertyDescriptor(target, key))
                      : desc,
            d;
        if (
            typeof Reflect === "object" &&
            typeof Reflect.decorate === "function"
        )
            r = Reflect.decorate(decorators, target, key, desc);
        else
            for (var i = decorators.length - 1; i >= 0; i--)
                if ((d = decorators[i]))
                    r =
                        (c < 3
                            ? d(r)
                            : c > 3
                              ? d(target, key, r)
                              : d(target, key)) || r;
        return c > 3 && r && Object.defineProperty(target, key, r), r;
    };
import axios from "axios";
import { injectable } from "tsyringe";
var Ajax = (function () {
    function Ajax() {
        this.params = {};
        this.route = "";
    }
    Ajax.prototype.doAjaxCall = function (
        method,
        successCallBack,
        errorCallBack,
    ) {
        var _this = this;
        if (method.toLowerCase() === "get") {
            this.getRequest(this.route, this.params)
                .then(function (result) {
                    return successCallBack(result);
                })
                .catch(function (error) {
                    if (typeof error.response !== "undefined") {
                        var response = error.response;
                        if (response.status === 401) {
                            window.location.reload();
                        }
                        if (response.status === 429) {
                            _this.initiateGlobalTimeOut();
                        }
                    }
                    return errorCallBack(error);
                });
        }
        if (method.toLowerCase() === "post") {
            this.postRequest(this.route, this.params)
                .then(function (result) {
                    return successCallBack(result);
                })
                .catch(function (error) {
                    if (typeof error.response !== "undefined") {
                        var response = error.response;
                        if (response.status === 401) {
                            window.location.reload();
                        }
                        if (response.status === 429) {
                            _this.initiateGlobalTimeOut();
                        }
                    }
                    return errorCallBack(error);
                });
        }
    };
    Ajax.prototype.setParameters = function (params) {
        this.params = params;
        return this;
    };
    Ajax.prototype.setRoute = function (route) {
        this.route = route;
        return this;
    };
    Ajax.prototype.getRequest = function (url, params) {
        return axios.get("/api/" + url, { params: params });
    };
    Ajax.prototype.postRequest = function (url, params) {
        return axios.post("/api/" + url, params);
    };
    Ajax.prototype.initiateGlobalTimeOut = function () {
        this.setRoute("character-timeout").doAjaxCall(
            "post",
            function (result) {},
            function (error) {
                console.error(error);
            },
        );
    };
    Ajax = __decorate([injectable()], Ajax);
    return Ajax;
})();
export default Ajax;
//# sourceMappingURL=ajax.js.map
