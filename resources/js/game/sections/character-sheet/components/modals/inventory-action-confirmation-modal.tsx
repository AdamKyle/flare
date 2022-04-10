import React, {Fragment} from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import {AdditionalInfoModalProps} from "../../../../lib/game/character-sheet/types/modal/additional-info-modal-props";
import Tabs from "../../../../components/ui/tabs/tabs";
import TabPanel from "../../../../components/ui/tabs/tab-panel";
import {formatNumber} from "../../../../lib/game/format-number";
import ComponentLoading from "../../../../components/ui/loading/component-loading";
import {AxiosError, AxiosResponse} from "axios";
import Ajax from "../../../../lib/ajax/ajax";
import InventoryItemComparisonState
    from "../../../../lib/game/character-sheet/types/modal/inventory-item-comparison-state";
import ItemNameColorationText from "../../../../components/ui/item-name-coloration-text";
import InventoryComparisonAdjustment
    from "../../../../lib/game/character-sheet/types/modal/inventory-comparison-adjustment";
import {capitalize} from "lodash";
import PrimaryOutlineButton from "../../../../components/ui/buttons/primary-outline-button";
import SuccessOutlineButton from "../../../../components/ui/buttons/success-outline-button";
import DangerOutlineButton from "../../../../components/ui/buttons/danger-outline-button";
import clsx from "clsx";
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
            (new Ajax()).setRoute(this.props.url).doAjaxCall('post', (result: AxiosResponse) => {
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
