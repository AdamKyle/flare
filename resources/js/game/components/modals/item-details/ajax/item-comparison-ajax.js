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
var ItemComparisonAjax = (function () {
    function ItemComparisonAjax(ajax) {
        this.ajax = ajax;
    }
    ItemComparisonAjax.prototype.fetchChatComparisonData = function (
        component,
    ) {
        this.ajax
            .setRoute(
                "character/" +
                    component.props.character_id +
                    "/inventory/comparison-from-chat",
            )
            .setParameters({
                id: component.props.slot_id,
            })
            .doAjaxCall(
                "get",
                function (result) {
                    component.setState({
                        loading: false,
                        comparison_details: result.data.comparison_data,
                        usable_sets: result.data.usable_sets,
                    });
                },
                function (error) {
                    component.setState({ loading: false });
                    if (typeof error.response !== "undefined") {
                        var response = error.response;
                        if (response.status === 404) {
                            component.setState({
                                error_message:
                                    "Item no longer exists in your inventory...",
                            });
                            return;
                        }
                        component.setState({
                            error_message: error.response.data.message,
                        });
                    }
                },
            );
    };
    ItemComparisonAjax = __decorate(
        [
            injectable(),
            __param(0, inject(Ajax)),
            __metadata("design:paramtypes", [Object]),
        ],
        ItemComparisonAjax,
    );
    return ItemComparisonAjax;
})();
export default ItemComparisonAjax;
//# sourceMappingURL=item-comparison-ajax.js.map
