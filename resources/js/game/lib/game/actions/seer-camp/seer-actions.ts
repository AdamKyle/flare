import SeerCamp from "../../../../sections/game-actions-section/components/crafting-sections/seer-camp";
import Ajax from "../../../ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import ManageGems from "../../../../sections/components/gems/manage-gems";

export default class SeerActions {

    static handleInitialFetch(component: SeerCamp) {
        (new Ajax()).setRoute('visit-seer-camp/' + component.props.character_id).doAjaxCall('get', (result: AxiosResponse) => {
            component.setState({
                items: result.data.items,
                gems: result.data.gems,
                is_loading: false,
            })
        }, (error: AxiosResponse) => {
            console.error(error);
        })
    }

    static manageSocketsOnItem(component: SeerCamp, slotId: number) {
        (new Ajax()).setRoute('seer-camp/add-sockets/' + component.props.character_id)
            .setParameters({slot_id: slotId})
            .doAjaxCall('post', (result: AxiosResponse) => {
                component.setState({
                    items: result.data.items,
                    gems: result.data.gems,
                    trading_with_seer: false,
                    success_message: result.data.message,
                })
            }, (error: AxiosError) => {
                component.setState({
                    trading_with_seer: false,
                }, () => {
                    if (typeof error.response !== 'undefined') {
                        const response = error.response;

                        component.setState({
                            error_message: response.data.message,
                        });
                    }
                });
            });
    }

    static attachGemToItem<T>(component: ManageGems<T>, slotId: number, gemSlotId: number) {
        (new Ajax()).setRoute('seer-camp/add-gem/' + component.props.character_id)
            .setParameters({slot_id: slotId, gem_slot_id: gemSlotId})
            .doAjaxCall('post', (result: AxiosResponse) => {
                component.setState({
                    trading_with_seer: false,
                }, () => {
                    component.props.update_parent(result.data.message, 'success_message');
                    component.props.update_parent(result.data.items, 'items');
                    component.props.update_parent(result.data.gems, 'gems');
                    component.props.manage_model();
                })
            }, (error: AxiosError) => {
                component.setState({
                    trading_with_seer: false,
                }, () => {
                    if (typeof error.response !== 'undefined') {
                        const response = error.response;

                        component.setState({
                            error_message: response.data.message,
                        });
                    }
                });
            });
    }
}
