import React from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import {AxiosError, AxiosResponse} from "axios";
import Ajax from "../../../../lib/ajax/ajax";
import InventoryActionConfirmationModalProps
    from "../../../../lib/game/character-sheet/types/modal/inventory-action-confirmation-modal-props";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import InventoryActionConfirmationModalState
    from "../../../../lib/game/character-sheet/types/modal/inventory-action-confirmation-modal-state";

export default class InventoryActionConfirmationModal extends React.Component<InventoryActionConfirmationModalProps, InventoryActionConfirmationModalState> {

    constructor(props: InventoryActionConfirmationModalProps) {
        super(props);

        this.state = {
            loading: false,
        }
    }

    confirm() {
        this.setState({
            loading: true
        }, () => {
            let ajax = (new Ajax()).setRoute(this.props.url);

            if (this.props.ajax_params) {
                ajax = ajax.setParameters(this.props.ajax_params);
            }

            ajax.doAjaxCall('post', (result: AxiosResponse) => {
                this.setState({
                    loading: false,
                }, () => {

                    if (result.data.hasOwnProperty('inventory')) {
                        this.props.update_inventory(result.data.inventory);
                    }

                    this.props.set_success_message(result.data.message);

                    this.props.manage_modal();
                });
            }, (error: AxiosError) => {

            });
        });
    }

    render() {

        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={this.props.title}
                      primary_button_disabled={this.state.loading}
                      secondary_actions={{
                          secondary_button_disabled: this.state.loading,
                          secondary_button_label: 'Yes. I understand.',
                          handle_action: this.confirm.bind(this),
                      }}
            >
                {this.props.children}

                {
                    this.state.loading ?
                        <LoadingProgressBar />
                    : null
                }

            </Dialogue>
        );
    }
}
