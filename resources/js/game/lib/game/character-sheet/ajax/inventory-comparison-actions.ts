import InventoryItemComparison from "../../../../sections/character-sheet/components/modals/inventory-item-comparison";
import Ajax from "../../../ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import ComparisonSection
    from "../../../../sections/character-sheet/components/modals/components/inventory-comparison/comparison-section";

type EquipParams = { position?: string, slot_id: number, equip_type: string };

type MoveItemParams = {move_to_set: number; slot_id: string | number | boolean | undefined};

type SellItem = {slot_id: string | number | boolean | undefined};

type DestroyItem = SellItem;

type ListItem = {list_for: number; slot_id: string | number | boolean | undefined};

export default class InventoryComparisonActions {

    equipItem(component: ComparisonSection, params: EquipParams) {
        (new Ajax()).setRoute('character/'+component.props.character_id+'/inventory/equip-item')
                    .setParameters(params)
                    .doAjaxCall('post', (result: AxiosResponse) => {

                        component.props.set_action_loading();

                        if (typeof component.props.update_inventory !== 'undefined') {
                            component.props.update_inventory(result.data.inventory);
                        }

                        if (typeof component.props.set_success_message !== 'undefined') {
                            component.props.set_success_message(result.data.message);
                        }

                        component.props.manage_modal();
                    }, (error: AxiosError) => {
                        if (typeof error.response !== 'undefined') {
                            component.setState({error_message: error.response.data.message}, () => {
                                component.props.set_action_loading();
                            })
                        }
                    });
    }

    moveItem(component: ComparisonSection, params: MoveItemParams) {
        (new Ajax()).setRoute('character/'+component.props.character_id+'/inventory/move-to-set')
                    .setParameters(params)
                    .doAjaxCall('post', (result: AxiosResponse) => {
                        component.props.set_action_loading();

                        if (typeof component.props.update_inventory !== 'undefined') {
                            component.props.update_inventory(result.data.inventory);
                        }

                        if (typeof component.props.set_success_message !== 'undefined') {
                            component.props.set_success_message(result.data.message);
                        }

                        component.props.manage_modal();
                    }, (error: AxiosError) => {

                    });
    }

    sellItem(component: ComparisonSection, params: SellItem) {
        (new Ajax()).setRoute('character/'+component.props.character_id+'/inventory/sell-item')
                    .setParameters(params)
                    .doAjaxCall('post', (result: AxiosResponse) => {
                        component.props.set_action_loading();

                        if (typeof component.props.update_inventory !== 'undefined') {
                            component.props.update_inventory(result.data.inventory);
                        }

                        if (typeof component.props.set_success_message !== 'undefined') {
                            component.props.set_success_message(result.data.message);
                        }

                        component.props.manage_modal();
                    }, (error: AxiosError) => {

                    });
    }

    listItem(component: ComparisonSection, params: ListItem) {
        (new Ajax()).setRoute('market-board/sell-item/' + component.props.character_id)
                    .setParameters(params)
                    .doAjaxCall('post', (result: AxiosResponse) => {
                        component.props.set_action_loading();

                        if (typeof component.props.update_inventory !== 'undefined') {
                            component.props.update_inventory(result.data.inventory);
                        }

                        if (typeof component.props.set_success_message !== 'undefined') {
                            component.props.set_success_message(result.data.message);
                        }

                        component.props.manage_modal();
                    }, (error: AxiosError) => {

                    });
    }

    disenchantItem(component: ComparisonSection) {
        (new Ajax()).setRoute('disenchant/' + component.props.comparison_details?.itemToEquip.id)
                    .doAjaxCall('post', (result: AxiosResponse) => {
                        component.props.set_action_loading();

                        if (typeof component.props.update_inventory !== 'undefined') {
                            component.props.update_inventory(result.data.inventory);
                        }

                        if (typeof component.props.set_success_message !== 'undefined') {
                            component.props.set_success_message(result.data.message);
                        }

                        component.props.manage_modal();
                    }, (error: AxiosError) => {

                    });
    }

    destroyItem(component: ComparisonSection, params: DestroyItem) {
        (new Ajax()).setRoute('character/'+component.props.character_id+'/inventory/destroy')
                    .setParameters(params)
                    .doAjaxCall('post', (result: AxiosResponse) => {
                        component.props.set_action_loading();

                        if (typeof component.props.update_inventory !== 'undefined') {
                            component.props.update_inventory(result.data.inventory);
                        }

                        if (typeof component.props.set_success_message !== 'undefined') {
                            component.props.set_success_message(result.data.message);
                        }

                        component.props.manage_modal();
                    }, (error: AxiosError) => {

                    });
    }
}
