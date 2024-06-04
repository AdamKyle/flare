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
var __metadata =
    (this && this.__metadata) ||
    function (k, v) {
        if (
            typeof Reflect === "object" &&
            typeof Reflect.metadata === "function"
        )
            return Reflect.metadata(k, v);
    };
var __param =
    (this && this.__param) ||
    function (paramIndex, decorator) {
        return function (target, key) {
            decorator(target, key, paramIndex);
        };
    };
import { inject, injectable } from "tsyringe";
import Ajax from "../../../lib/ajax/ajax";
var KingdomResourceTransferAjax = (function () {
    function KingdomResourceTransferAjax(ajax) {
        this.ajax = ajax;
    }
    KingdomResourceTransferAjax.prototype.fetchKingdomsToTransferFrom =
        function (component, characterId, kingdomId) {
            this.ajax
                .setRoute(
                    "kingdoms/" +
                        kingdomId +
                        "/" +
                        characterId +
                        "/resource-transfer-request",
                )
                .doAjaxCall(
                    "get",
                    function (result) {
                        component.setState({
                            loading: false,
                            kingdoms: result.data.kingdoms,
                            can_go_forward: result.data.kingdoms.length >= 2,
                        });
                    },
                    function (error) {
                        component.setState({
                            loading: false,
                        });
                        if (typeof error.response !== "undefined") {
                            var response = error.response;
                            component.setState({
                                error_message: response.data.message,
                            });
                        }
                    },
                );
        };
    KingdomResourceTransferAjax.prototype.requestResources = function (
        component,
        params,
        characterId,
    ) {
        this.ajax
            .setRoute("kingdom/" + characterId + "/send-request-for-resources")
            .setParameters(params)
            .doAjaxCall(
                "post",
                function (result) {
                    component.setState({
                        requesting: false,
                        kingdoms: result.data.kingdoms,
                        can_go_forward: result.data.kingdoms.length >= 2,
                        success_message: result.data.message,
                    });
                },
                function (error) {
                    component.setState({
                        requesting: false,
                    });
                    if (typeof error.response !== "undefined") {
                        var response = error.response;
                        component.setState({
                            error_message: response.data.message,
                        });
                    }
                },
            );
    };
    KingdomResourceTransferAjax = __decorate(
        [
            injectable(),
            __param(0, inject(Ajax)),
            __metadata("design:paramtypes", [Object]),
        ],
        KingdomResourceTransferAjax,
    );
    return KingdomResourceTransferAjax;
})();
export default KingdomResourceTransferAjax;
//# sourceMappingURL=kingdom-resource-transfer-ajax.js.map
