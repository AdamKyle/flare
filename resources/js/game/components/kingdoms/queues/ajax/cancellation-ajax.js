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
import Ajax from "../../../../lib/ajax/ajax";
import { CancellationType } from "../enums/cancellation-type";
var CancellationAjax = (function () {
    function CancellationAjax(ajax) {
        this.ajax = ajax;
    }
    CancellationAjax.prototype.doAjaxCall = function (
        component,
        cancellationType,
        queueData,
        characterId,
    ) {
        var route = this.getRoute(cancellationType, queueData, characterId);
        this.ajax
            .setRoute(route)
            .setParameters(this.getParameters(cancellationType, queueData))
            .doAjaxCall(
                "post",
                function (result) {
                    component.setState({
                        loading: false,
                        success_message: result.data.message,
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
    CancellationAjax.prototype.getRoute = function (
        cancellationType,
        queueData,
        characterId,
    ) {
        switch (cancellationType) {
            case CancellationType.BUILDING_IN_QUEUE:
                return "kingdoms/building-upgrade/cancel";
            case CancellationType.BUILDING_EXPANSION:
                return (
                    "kingdom/building-expansion/cancel-expand/" +
                    queueData.id +
                    "/" +
                    characterId
                );
            case CancellationType.UNIT_MOVEMENT:
                return "recall-units/" + queueData.id + "/" + characterId;
            case CancellationType.UNIT_RECRUITMENT:
                return "kingdoms/recruit-units/cancel";
            default:
                throw Error("Cannot create route.");
        }
    };
    CancellationAjax.prototype.getParameters = function (
        cancellationType,
        queueData,
    ) {
        switch (cancellationType) {
            case CancellationType.BUILDING_IN_QUEUE:
                return { queue_id: queueData.id };
            case CancellationType.UNIT_RECRUITMENT:
                return { queue_id: queueData.id };
            default:
                return {};
        }
    };
    CancellationAjax = __decorate(
        [
            injectable(),
            __param(0, inject(Ajax)),
            __metadata("design:paramtypes", [Object]),
        ],
        CancellationAjax,
    );
    return CancellationAjax;
})();
export default CancellationAjax;
//# sourceMappingURL=cancellation-ajax.js.map
