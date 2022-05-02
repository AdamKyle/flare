import InventoryItemComparison from "../../../../sections/character-sheet/components/modals/inventory-item-comparison";
import Ajax from "../../../ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";

type EquipParams = { position?: string, slot_id: number, equip_type: string };

type MoveItemParams = {move_to_set: number; slot_id: string | number | boolean | undefined};

type SellItem = {slot_id: string | number | boolean | undefined};

type DestroyItem = SellItem;

type ListItem = {list_for: number; slot_id: string | number | boolean | undefined};

export default class InventoryComparisonActions {

    equipItem(component: InventoryItemComparison, params: EquipParams) {
        (new Ajax()).setRoute('character/'+component.props.character_id+'/inventory/equip-item')
                    .setParameters(params)
                    .doAjaxCall('post', (result: AxiosResponse) => {
                        component.setState({
                            action_loading: false,
                        }, () => {
                            component.props.update_inventory(result.data.inventory);

                            component.props.set_success_message(result.data.message);

                            component.props.manage_modal();
                        })
                    }, (error: AxiosError) => {

                    });
    }

    moveItem(component: InventoryItemComparison, params: MoveItemParams) {
        (new Ajax()).setRoute('character/'+component.props.character_id+'/inventory/move-to-set')
                    .setParameters(params)
                    .doAjaxCall('post', (result: AxiosResponse) => {
                        component.setState({
                            action_loading: false,
                        }, () => {
                            component.props.update_inventory(result.data.inventory);

                            component.props.set_success_message(result.data.message);

                            component.props.manage_modal();
                        })
                    }, (error: AxiosError) => {

                    });
    }

    sellItem(component: InventoryItemComparison, params: SellItem) {
        (new Ajax()).setRoute('character/'+component.props.character_id+'/inventory/sell-item')
                    .setParameters(params)
                    .doAjaxCall('post', (result: AxiosResponse) => {
                        component.setState({
                            action_loading: false,
                        }, () => {
                            component.props.update_inventory(result.data.inventory);

                            component.props.set_success_message(result.data.message);

                            component.props.manage_modal();
                        })
                    }, (error: AxiosError) => {

                    });
    }

    listItem(component: InventoryItemComparison, params: ListItem) {
        (new Ajax()).setRoute('market-board/sell-item/' + component.props.character_id)
                    .setParameters(params)
                    .doAjaxCall('post', (result: AxiosResponse) => {
                        component.setState({
                            action_loading: false,
                        }, () => {
                            component.props.update_inventory(result.data.inventory);

                            component.props.set_success_message(result.data.message);

                            component.props.manage_modal();
                        })
                    }, (error: AxiosError) => {

                    });
    }

    disenchantItem(component: InventoryItemComparison) {
        (new Ajax()).setRoute('disenchant/' + component.state.comparison_details?.itemToEquip.id)
                    .doAjaxCall('post', (result: AxiosResponse) => {
                        component.setState({
                            action_loading: false,
                        }, () => {
                            component.props.update_inventory(result.data.inventory);

                            component.props.set_success_message(result.data.message);

                            component.props.manage_modal();
                        })
                    }, (error: AxiosError) => {

                    });
    }

    destroyItem(component: InventoryItemComparison, params: DestroyItem) {
        (new Ajax()).setRoute('character/'+component.props.character_id+'/inventory/destroy')
                    .setParameters(params)
                    .doAjaxCall('post', (result: AxiosResponse) => {
                        component.setState({
                            action_loading: false,
                        }, () => {
                            component.props.update_inventory(result.data.inventory);

                            component.props.set_success_message(result.data.message);

                            component.props.manage_modal();
                        })
                    }, (error: AxiosError) => {

                    });
    }
}
