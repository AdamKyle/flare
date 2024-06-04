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
import Ajax from "../../../../lib/ajax/ajax";
import { inject, singleton } from "tsyringe";
var InventoryComparisonActionsAjax = (function () {
    function InventoryComparisonActionsAjax(ajax) {
        this.ajax = ajax;
    }
    InventoryComparisonActionsAjax.prototype.equipItem = function (
        component,
        params,
    ) {
        var _this = this;
        this.ajax
            .setRoute(
                "character/" +
                    component.props.character_id +
                    "/inventory/equip-item",
            )
            .setParameters(params)
            .doAjaxCall(
                "post",
                function (result) {
                    _this.handleSuccess(component, result);
                },
                function (error) {
                    _this.handleError(component, error);
                },
            );
    };
    InventoryComparisonActionsAjax.prototype.moveItem = function (
        component,
        params,
    ) {
        var _this = this;
        this.ajax
            .setRoute(
                "character/" +
                    component.props.character_id +
                    "/inventory/move-to-set",
            )
            .setParameters(params)
            .doAjaxCall(
                "post",
                function (result) {
                    _this.handleSuccess(component, result);
                },
                function (error) {
                    _this.handleError(component, error);
                },
            );
    };
    InventoryComparisonActionsAjax.prototype.sellItem = function (
        component,
        params,
    ) {
        var _this = this;
        this.ajax
            .setRoute(
                "character/" +
                    component.props.character_id +
                    "/inventory/sell-item",
            )
            .setParameters(params)
            .doAjaxCall(
                "post",
                function (result) {
                    _this.handleSuccess(component, result);
                },
                function (error) {
                    _this.handleError(component, error);
                },
            );
    };
    InventoryComparisonActionsAjax.prototype.listItem = function (
        component,
        params,
    ) {
        var _this = this;
        this.ajax
            .setRoute("market-board/sell-item/" + component.props.character_id)
            .setParameters(params)
            .doAjaxCall(
                "post",
                function (result) {
                    _this.handleSuccess(component, result);
                },
                function (error) {
                    _this.handleError(component, error);
                },
            );
    };
    InventoryComparisonActionsAjax.prototype.disenchantItem = function (
        component,
    ) {
        var _this = this;
        var _a;
        this.ajax
            .setRoute(
                "disenchant/" +
                    ((_a = component.props.comparison_details) === null ||
                    _a === void 0
                        ? void 0
                        : _a.itemToEquip.id),
            )
            .doAjaxCall(
                "post",
                function (result) {
                    _this.handleSuccess(component, result);
                },
                function (error) {
                    _this.handleError(component, error);
                },
            );
    };
    InventoryComparisonActionsAjax.prototype.destroyItem = function (
        component,
        params,
    ) {
        var _this = this;
        this.ajax
            .setRoute(
                "character/" +
                    component.props.character_id +
                    "/inventory/destroy",
            )
            .setParameters(params)
            .doAjaxCall(
                "post",
                function (result) {
                    _this.handleSuccess(component, result);
                },
                function (error) {
                    _this.handleError(component, error);
                },
            );
    };
    InventoryComparisonActionsAjax.prototype.handleSuccess = function (
        component,
        result,
    ) {
        component.setState({
            show_loading_label: false,
            loading_label: null,
        });
        if (component.props.update_inventory) {
            component.props.update_inventory(result.data.inventory);
        }
        if (component.props.set_success_message) {
            component.props.set_success_message(result.data.message);
        } else {
            component.setState({
                success_message: result.data.message,
                has_updated_item: true,
            });
        }
        if (component.props.manage_modal && component.props.update_inventory) {
            component.props.manage_modal();
        }
    };
    InventoryComparisonActionsAjax.prototype.handleError = function (
        component,
        error,
    ) {
        component.setState({
            show_loading_label: false,
            loading_label: null,
        });
        if (typeof error.response !== "undefined") {
            component.setState({ error_message: error.response.data.message });
        }
    };
    InventoryComparisonActionsAjax = __decorate(
        [
            singleton(),
            __param(0, inject(Ajax)),
            __metadata("design:paramtypes", [Ajax]),
        ],
        InventoryComparisonActionsAjax,
    );
    return InventoryComparisonActionsAjax;
})();
export default InventoryComparisonActionsAjax;
//# sourceMappingURL=inventory-comparison-actions-ajax.js.map
