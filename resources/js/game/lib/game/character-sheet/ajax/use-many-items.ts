import InventoryUseManyItems from "../../../../sections/character-sheet/components/modals/inventory-use-many-items";
import Ajax from "../../../ajax/ajax";
import {AxiosResponse} from "axios";

export default class UseManyItems {

    private itemsToUse: {item_name: string, item_id: number}[];

    private component: InventoryUseManyItems;

    constructor(itemIds: {item_name: string, item_id: number}[], component: InventoryUseManyItems) {
        this.itemsToUse = itemIds;

        this.component = component;
    }

    postEachItem(characterId: number) {

        this.itemsToUse.forEach((itemToUse: {item_name: string, item_id: number}, index: number) => {
            const nextItem = this.itemsToUse[index + 1];

            this.component.setState({
                using_item: itemToUse.item_name,
                loading: true,
            });

            (new Ajax()).setRoute('character/'+characterId+'/inventory/use-item/' + itemToUse.item_id).doAjaxCall('post', (result: AxiosResponse) => {
                if (typeof nextItem === 'undefined' ) {
                    this.component.setState({
                        item_progress: 0,
                        using_item: null,
                        loading: false,
                    }, () => {
                        this.component.props.set_success_message('Used all selected items.');

                        this.component.props.manage_modal();
                    });
                } else {
                    this.component.setState({
                        item_progress: this.component.state.item_progress + 1,
                        using_item: nextItem.item_name,
                    });
                }

                this.component.props.update_inventory(result.data.inventory);
            }, (error: AxiosResponse) => {

            })
        })
    }


}
