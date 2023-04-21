import SeerCamp from "../../../../sections/game-actions-section/components/crafting-sections/seer-camp";
import Ajax from "../../../ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import ManageGems from "../../../../sections/components/gems/manage-gems";
import AtonementComparison from "../../../../sections/components/gems/atonement-comparison";
import RemoveGemComparison from "../../../../sections/components/gems/remove-gem-comparison";

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

    static replaceGemOnItem(component: AtonementComparison, slotId: number, gemSlotId: number, gemSocketId: number) {
        (new Ajax()).setRoute('seer-camp/replace-gem/' + component.props.character_id)
            .setParameters({slot_id: slotId, gem_slot_id: gemSlotId, gem_slot_to_replace: gemSocketId})
            .doAjaxCall('post', (result: AxiosResponse) => {
                component.setState({
                    is_replacing: false,
                }, () => {
                    component.props.update_parent(result.data.message, 'success_message');
                    component.props.update_parent(result.data.items, 'items');
                    component.props.update_parent(result.data.gems, 'gems');
                    component.closeModals();
                })
            }, (error: AxiosError) => {
                component.setState({
                    is_replacing: false,
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

    static removeGem(component: RemoveGemComparison, slotId: number, gemId: number) {
        (new Ajax()).setRoute('seer-camp/remove-gem/' + component.props.character_id)
            .setParameters({slot_id: slotId, gem_id: gemId})
            .doAjaxCall('post', (result: AxiosResponse) => {
                component.setState({
                    is_removing: false,
                }, () => {
                    component.props.update_parent(result.data.message, 'success_message');
                    component.props.update_parent(result.data.items, 'items');
                    component.props.update_parent(result.data.gems, 'gems');
                    component.props.update_remomal_data(result.data.removal_data.items, 'items')
                    component.props.update_remomal_data(result.data.removal_data.gems, 'gems')
                    component.props.manage_modal();
                })
            }, (error: AxiosError) => {
                component.setState({
                    is_removing: false,
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

    static removeAllGems(component: RemoveGemComparison, slotId: number) {
        (new Ajax()).setRoute('seer-camp/remove-all-gems/' + component.props.character_id + '/' + slotId)
            .doAjaxCall('post', (result: AxiosResponse) => {
                component.setState({
                    is_removing: false,
                }, () => {
                    component.props.update_parent(result.data.message, 'success_message');
                    component.props.update_parent(result.data.items, 'items');
                    component.props.update_parent(result.data.gems, 'gems');
                    component.props.update_remomal_data(result.data.removal_data.items, 'items')
                    component.props.update_remomal_data(result.data.removal_data.gems, 'gems')
                    component.props.manage_modal();
                })
            }, (error: AxiosError) => {
                component.setState({
                    is_removing: false,
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
