import { inject, injectable } from "tsyringe";
import AjaxInterface from "../../../../lib/ajax/ajax-interface";
import Ajax from "../../../../lib/ajax/ajax";
import ItemDetailsModal from "../item-details-modal";
import { AxiosError, AxiosResponse } from "axios";

@injectable()
export default class ItemComparisonAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public fetchChatComparisonData(component: ItemDetailsModal) {
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
                (result: AxiosResponse) => {
                    component.setState({
                        loading: false,
                        comparison_details: result.data.comparison_data,
                        usable_sets: result.data.usable_sets,
                    });
                },
                (error: AxiosError) => {
                    component.setState({ loading: false });

                    if (typeof error.response !== "undefined") {
                        const response: AxiosResponse = error.response;

                        if (response.status === 404) {
                            component.setState({
                                error_message:
                                    "Item no longer exists in your inventory...",
                            });

                            return;
                        }

                        component.setState({
                            error_message: response.data.message,
                        });
                    }
                },
            );
    }
}
