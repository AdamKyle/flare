import {inject, injectable} from "tsyringe";
import Ajax from "../../lib/ajax/ajax.js";
import AjaxInterface from "../../lib/ajax/ajax-interface.js";
import Shop from "../shop";
import {AxiosError, AxiosResponse, Method} from "axios";


export enum SHOP_ACTIONS {
    FETCH = 'fetch',
    COMPARE = 'compare-and-buy',
    BUY = 'buy',
    BUY_MANY = 'buy-many'
}

@injectable()
export default class ShopAjax {

    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public doShopAction(component: Shop, actionType: SHOP_ACTIONS, params?: any) {
        const route = this.getRoute(actionType, component.props.character_id);
        const actionForRoute = this.getActionType(actionType);

        this.ajax.setRoute(route).setParameters(params).doAjaxCall(actionForRoute, (result: AxiosResponse) => {
            component.setState({
                loading: false
            }, () => {
                this.processResponse(component, actionType, result.data);
            })
        }, (error: AxiosError) => {
            component.setState({
                loading: false
            });

            if (typeof error.response !== 'undefined') {
                const response = error.response;

                component.setState({
                    error_message: response.data.message,
                });
            }
        })
    }

    protected getRoute(actionType: SHOP_ACTIONS, characterId: number): string {
        switch(actionType) {
            case SHOP_ACTIONS.FETCH:
                return 'character/'+characterId+'/visit-shop';
            default:
                throw new Error('Unknown route to take.')
        }
    }

    protected getActionType(actionType: SHOP_ACTIONS): Method {
        switch(actionType) {
            case SHOP_ACTIONS.FETCH:
                return 'get'
            case SHOP_ACTIONS.COMPARE:
            case SHOP_ACTIONS.BUY:
            case SHOP_ACTIONS.BUY_MANY:
                return 'post'
            default:
                throw new Error('Unknown action to take for route.')
        }
    }

    protected processResponse(component: Shop, actionType: SHOP_ACTIONS, axiosData: any): void {
        switch (actionType) {
            case SHOP_ACTIONS.FETCH:
                return this.handleFetchData(component, axiosData)
            default:
                throw new Error('Cannot figure out what to do with axios data from shop action');
        }
    }

    private handleFetchData(component: Shop, axiosData: any): void {
        component.setState({
            items: axiosData.items,
            original_items: axiosData.items,
        })
    }
}
