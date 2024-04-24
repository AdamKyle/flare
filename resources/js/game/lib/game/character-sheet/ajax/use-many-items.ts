import InventoryUseManyItems from "../../../../sections/character-sheet/components/modals/inventory-use-many-items";
import Ajax from "../../../ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";

export default class UseManyItems {
    private itemsToUse: number[];

    private component: InventoryUseManyItems;

    constructor(itemIds: number[], component: InventoryUseManyItems) {
        this.itemsToUse = itemIds;

        this.component = component;
    }

    useAllItems(characterId: number) {
        new Ajax()
            .setRoute("character/" + characterId + "/inventory/use-many-items")
            .setParameters({ items_to_use: this.itemsToUse })
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    this.component.setState(
                        {
                            using_item: null,
                            loading: false,
                        },
                        () => {
                            this.component.props.set_success_message(
                                "Used all selected items.",
                            );

                            this.component.props.update_inventory(
                                result.data.inventory,
                            );

                            this.component.props.manage_modal();
                        },
                    );
                },
                (error: AxiosError) => {
                    if (typeof error.response !== "undefined") {
                        const response: AxiosResponse = error.response;

                        this.component.setState({
                            loading: false,
                            error_message: response.data.message,
                        });
                    }
                },
            );
    }
}
