import { inject, injectable } from "tsyringe";
import Ajax from "../../../../game/lib/ajax/ajax.js";
import AjaxInterface from "../../../../game/lib/ajax/ajax-interface.js";
import Shop from "../shop";
import { AxiosError, AxiosResponse, Method } from "axios";
import BuyMultiple from "../buy-multiple";
import BuyAndCompare from "../buy-and-compare";

export enum SHOP_ACTIONS {
    FETCH = "fetch",
    COMPARE = "compare-and-buy",
    BUY_AND_REPLACE = "buy-and-replace",
    BUY = "buy",
    BUY_MANY = "buy-many",
}

@injectable()
export default class ShopAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public doShopAction(
        component: Shop | BuyMultiple | BuyAndCompare,
        actionType: SHOP_ACTIONS,
        params?: any,
    ) {
        const route = this.getRoute(actionType, component.props.character_id);
        const actionForRoute = this.getActionType(actionType);

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
    }

    protected handleBuy(
        component: Shop,
        route: string,
        action: Method,
        params?: any,
    ) {
        component.setState({ loading: true });

        this.ajax
            .setRoute(route)
            .setParameters(params)
            .doAjaxCall(
                action,
                (result: AxiosResponse) => {
                    component.setState({
                        loading: false,
                        success_message: result.data.message,
                    });
                },
                (error: AxiosError) => {
                    component.setState({
                        loading: false,
                    });

                    if (typeof error.response !== "undefined") {
                        const response: AxiosResponse = error.response;

                        component.setState({
                            error_message: response.data.message,
                        });
                    }
                },
            );
    }

    protected handleBuyingMany(
        component: BuyMultiple,
        route: string,
        action: Method,
        params?: any,
    ) {
        component.setState({ loading: true });

        this.ajax
            .setRoute(route)
            .setParameters(params)
            .doAjaxCall(
                action,
                (result: AxiosResponse) => {
                    component.setState({
                        loading: false,
                        success_message: result.data.message,
                    });
                },
                (error: AxiosError) => {
                    component.setState({
                        loading: false,
                    });

                    if (typeof error.response !== "undefined") {
                        const response: AxiosResponse = error.response;

                        component.setState({
                            error_message: response.data.message,
                        });
                    }
                },
            );
    }

    protected handleFetchShopContent(
        component: Shop,
        route: string,
        action: Method,
        params?: any,
    ) {
        component.setState({ loading: true });

        this.ajax
            .setRoute(route)
            .setParameters(params)
            .doAjaxCall(
                action,
                (result: AxiosResponse) => {
                    component.setState({
                        loading: false,
                        items: result.data.items,
                        gold: result.data.gold,
                        inventory_count: result.data.inventory_count,
                        inventory_max: result.data.inventory_max,
                        is_merchant: result.data.is_merchant,
                    });
                },
                (error: AxiosError) => {
                    component.setState({
                        loading: false,
                    });

                    if (typeof error.response !== "undefined") {
                        const response: AxiosResponse = error.response;

                        component.setState({
                            error_message: response.data.message,
                        });
                    }
                },
            );
    }

    protected handleComparisonFetch(
        component: BuyAndCompare,
        route: string,
        action: Method,
        params?: any,
    ) {
        component.setState({ loading: true });

        this.ajax
            .setRoute(route)
            .setParameters(params)
            .doAjaxCall(
                action,
                (result: AxiosResponse) => {
                    component.setState({
                        loading: false,
                        comparison_data: result.data.comparison_data,
                    });
                },
                (error: AxiosError) => {
                    component.setState({
                        loading: false,
                    });

                    if (typeof error.response !== "undefined") {
                        const response: AxiosResponse = error.response;

                        component.setState({
                            error_message: response.data.message,
                        });
                    }
                },
            );
    }

    protected handleBuyAndReplace(
        component: BuyAndCompare,
        route: string,
        action: Method,
        params?: any,
    ) {
        component.setState({ loading: true });

        this.ajax
            .setRoute(route)
            .setParameters(params)
            .doAjaxCall(
                action,
                (result: AxiosResponse) => {
                    component.setState(
                        {
                            loading: false,
                        },
                        () => {
                            component.props.set_success_message(
                                result.data.message,
                            );

                            component.props.close_view_buy_and_compare();
                        },
                    );
                },
                (error: AxiosError) => {
                    component.setState({
                        loading: false,
                    });

                    if (typeof error.response !== "undefined") {
                        const response: AxiosResponse = error.response;

                        component.setState({
                            error_message: response.data.message,
                        });
                    }
                },
            );
    }

    protected getRoute(actionType: SHOP_ACTIONS, characterId: number): string {
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
    }

    protected getActionType(actionType: SHOP_ACTIONS): Method {
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
    }
}
