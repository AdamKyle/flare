import Ajax from "../../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import ItemActions from "../item-actions";
import { inject, singleton } from "tsyringe";

type EquipParams = { position?: string; slot_id: number; equip_type: string };

type MoveItemParams = {
    move_to_set: number;
    slot_id: string | number | boolean | object | [];
};

type SellItem = { slot_id: string | number | boolean | object | [] };

type DestroyItem = SellItem;

type ListItem = { list_for: number; slot_id: number };

@singleton()
export default class InventoryComparisonActionsAjax {
    constructor(@inject(Ajax) private ajax: Ajax) {}

    equipItem(component: ItemActions, params: EquipParams) {
        this.ajax
            .setRoute(
                "character/" +
                    component.props.character_id +
                    "/inventory/equip-item",
            )
            .setParameters(params)
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    this.handleSuccess(component, result);
                },
                (error: AxiosError) => {
                    this.handleError(component, error);
                },
            );
    }

    moveItem(component: ItemActions, params: MoveItemParams) {
        this.ajax
            .setRoute(
                "character/" +
                    component.props.character_id +
                    "/inventory/move-to-set",
            )
            .setParameters(params)
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    this.handleSuccess(component, result);
                },
                (error: AxiosError) => {
                    this.handleError(component, error);
                },
            );
    }

    sellItem(component: ItemActions, params: SellItem) {
        this.ajax
            .setRoute(
                "character/" +
                    component.props.character_id +
                    "/inventory/sell-item",
            )
            .setParameters(params)
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    this.handleSuccess(component, result);
                },
                (error: AxiosError) => {
                    this.handleError(component, error);
                },
            );
    }

    listItem(component: ItemActions, params: ListItem) {
        this.ajax
            .setRoute("market-board/sell-item/" + component.props.character_id)
            .setParameters(params)
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    this.handleSuccess(component, result);
                },
                (error: AxiosError) => {
                    this.handleError(component, error);
                },
            );
    }

    disenchantItem(component: ItemActions) {
        this.ajax
            .setRoute(
                "disenchant/" +
                    component.props.comparison_details?.itemToEquip.id,
            )
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    this.handleSuccess(component, result);
                },
                (error: AxiosError) => {
                    this.handleError(component, error);
                },
            );
    }

    destroyItem(component: ItemActions, params: DestroyItem) {
        this.ajax
            .setRoute(
                "character/" +
                    component.props.character_id +
                    "/inventory/destroy",
            )
            .setParameters(params)
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    this.handleSuccess(component, result);
                },
                (error: AxiosError) => {
                    this.handleError(component, error);
                },
            );
    }

    protected handleSuccess(
        component: ItemActions,
        result: AxiosResponse,
    ): void {
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
    }

    protected handleError(component: ItemActions, error: AxiosError): void {
        component.setState({
            show_loading_label: false,
            loading_label: null,
        });

        if (typeof error.response !== "undefined") {
            const response: AxiosResponse = error.response;

            component.setState({ error_message: response.data.message });
        }
    }
}
