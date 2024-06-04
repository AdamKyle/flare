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
var BountyFightAjax = (function () {
    function BountyFightAjax(ajax) {
        this.ajax = ajax;
    }
    BountyFightAjax.prototype.doAjaxCall = function (
        component,
        fightData,
        characterId,
    ) {
        this.ajax
            .setRoute("faction-loyalty-bounty/" + characterId)
            .setParameters(fightData)
            .doAjaxCall(
                "post",
                function (result) {
                    component.setState({
                        attacking: false,
                        success_message: result.data.message,
                        must_revive: result.data.must_revive,
                    });
                },
                function (error) {
                    component.setState({
                        attacking: false,
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
    BountyFightAjax = __decorate(
        [
            injectable(),
            __param(0, inject(Ajax)),
            __metadata("design:paramtypes", [Object]),
        ],
        BountyFightAjax,
    );
    return BountyFightAjax;
})();
export default BountyFightAjax;
//# sourceMappingURL=bounty-fight-ajax.js.map
