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
import Ajax from "../../../../game/lib/ajax/ajax.js";
import Shop from "../shop";
import BuyMultiple from "../buy-multiple";
import BuyAndCompare from "../buy-and-compare";
export var SHOP_ACTIONS;
(function (SHOP_ACTIONS) {
    SHOP_ACTIONS["FETCH"] = "fetch";
    SHOP_ACTIONS["COMPARE"] = "compare-and-buy";
    SHOP_ACTIONS["BUY_AND_REPLACE"] = "buy-and-replace";
    SHOP_ACTIONS["BUY"] = "buy";
    SHOP_ACTIONS["BUY_MANY"] = "buy-many";
})(SHOP_ACTIONS || (SHOP_ACTIONS = {}));
var ShopAjax = (function () {
    function ShopAjax(ajax) {
        this.ajax = ajax;
    }
    ShopAjax.prototype.doShopAction = function (component, actionType, params) {
        var route = this.getRoute(actionType, component.props.character_id);
        var actionForRoute = this.getActionType(actionType);
        if (component instanceof Shop) {
            if (actionType === SHOP_ACTIONS.BUY) {
                return this.handleBuy(component, route, actionForRoute, params);
            }
            if (actionType === SHOP_ACTIONS.FETCH) {
                return this.handleFetchShopContent(
                    component,
                    route,
                    actionForRoute,
                    params,
                );
            }
        }
        if (component instanceof BuyMultiple) {
            return this.handleBuyingMany(
                component,
                route,
                actionForRoute,
                params,
            );
        }
        if (component instanceof BuyAndCompare) {
            if (actionType === SHOP_ACTIONS.COMPARE) {
                return this.handleComparisonFetch(
                    component,
                    route,
                    actionForRoute,
                    params,
                );
            }
            if (actionType === SHOP_ACTIONS.BUY_AND_REPLACE) {
                return this.handleBuyAndReplace(
                    component,
                    route,
                    actionForRoute,
                    params,
                );
            }
        }
    };
    ShopAjax.prototype.handleBuy = function (component, route, action, params) {
        component.setState({ loading: true });
        this.ajax
            .setRoute(route)
            .setParameters(params)
            .doAjaxCall(
                action,
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
    ShopAjax.prototype.handleBuyingMany = function (
        component,
        route,
        action,
        params,
    ) {
        component.setState({ loading: true });
        this.ajax
            .setRoute(route)
            .setParameters(params)
            .doAjaxCall(
                action,
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
    ShopAjax.prototype.handleFetchShopContent = function (
        component,
        route,
        action,
        params,
    ) {
        component.setState({ loading: true });
        this.ajax
            .setRoute(route)
            .setParameters(params)
            .doAjaxCall(
                action,
                function (result) {
                    component.setState({
                        loading: false,
                        items: result.data.items,
                        gold: result.data.gold,
                        inventory_count: result.data.inventory_count,
                        inventory_max: result.data.inventory_max,
                        is_merchant: result.data.is_merchant,
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
    ShopAjax.prototype.handleComparisonFetch = function (
        component,
        route,
        action,
        params,
    ) {
        component.setState({ loading: true });
        this.ajax
            .setRoute(route)
            .setParameters(params)
            .doAjaxCall(
                action,
                function (result) {
                    component.setState({
                        loading: false,
                        comparison_data: result.data.comparison_data,
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
    ShopAjax.prototype.handleBuyAndReplace = function (
        component,
        route,
        action,
        params,
    ) {
        component.setState({ loading: true });
        this.ajax
            .setRoute(route)
            .setParameters(params)
            .doAjaxCall(
                action,
                function (result) {
                    component.setState(
                        {
                            loading: false,
                        },
                        function () {
                            component.props.set_success_message(
                                result.data.message,
                            );
                            component.props.close_view_buy_and_compare();
                        },
                    );
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
    ShopAjax.prototype.getRoute = function (actionType, characterId) {
        switch (actionType) {
            case SHOP_ACTIONS.FETCH:
                return "character/" + characterId + "/visit-shop";
            case SHOP_ACTIONS.BUY:
                return "shop/buy/item/" + characterId;
            case SHOP_ACTIONS.BUY_MANY:
                return "shop/purchase/multiple/" + characterId;
            case SHOP_ACTIONS.COMPARE:
                return "shop/view/comparison/" + characterId;
            case SHOP_ACTIONS.BUY_AND_REPLACE:
                return "shop/buy-and-replace/" + characterId;
            default:
                throw new Error("Unknown route to take.");
        }
    };
    ShopAjax.prototype.getActionType = function (actionType) {
        switch (actionType) {
            case SHOP_ACTIONS.FETCH:
            case SHOP_ACTIONS.COMPARE:
                return "get";
            case SHOP_ACTIONS.BUY:
            case SHOP_ACTIONS.BUY_MANY:
            case SHOP_ACTIONS.BUY_AND_REPLACE:
                return "post";
            default:
                throw new Error("Unknown action to take for route.");
        }
    };
    ShopAjax = __decorate(
        [
            injectable(),
            __param(0, inject(Ajax)),
            __metadata("design:paramtypes", [Object]),
        ],
        ShopAjax,
    );
    return ShopAjax;
})();
export default ShopAjax;
//# sourceMappingURL=shop-ajax.js.map
