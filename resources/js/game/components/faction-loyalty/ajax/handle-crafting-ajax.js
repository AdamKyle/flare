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
var HandleCraftingAjax = (function () {
    function HandleCraftingAjax(ajax) {
        this.ajax = ajax;
    }
    HandleCraftingAjax.prototype.doAjaxCall = function (
        component,
        craftingData,
        characterId,
    ) {
        this.ajax
            .setRoute("craft/" + characterId)
            .setParameters(craftingData)
            .doAjaxCall(
                "post",
                function (result) {
                    component.setState(
                        {
                            crafting: false,
                        },
                        function () {
                            if (result.data.crafted_item) {
                                component.setState({
                                    success_message:
                                        "The Npc is joyful that you were able to craft the item!",
                                });
                            }
                            if (!result.data.crafted_item) {
                                component.setState({
                                    success_message:
                                        "The Npc is confused. You failed. Why? (See the server message section below to find out why. For mobile, click the Chat Tabs button and select Server Messages.)",
                                });
                            }
                        },
                    );
                },
                function (error) {
                    component.setState({
                        crafting: false,
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
    HandleCraftingAjax = __decorate(
        [
            injectable(),
            __param(0, inject(Ajax)),
            __metadata("design:paramtypes", [Object]),
        ],
        HandleCraftingAjax,
    );
    return HandleCraftingAjax;
})();
export default HandleCraftingAjax;
//# sourceMappingURL=handle-crafting-ajax.js.map
