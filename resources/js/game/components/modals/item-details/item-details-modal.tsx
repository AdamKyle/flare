import React, {ReactNode} from "react";
import {ItemDetailsModalProps} from "./types/item-details-modal-props";
import {ItemDetailsModalState} from "./types/item-details-modal-state";
import Dialogue from "../../ui/dialogue/dialogue";
import ItemDetailsModalTitle from "./item-details-modal-title";
import {serviceContainer} from "../../../lib/containers/core-container";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import ItemView from "./item-view";
import DangerAlert from "../../ui/alerts/simple-alerts/danger-alert";
import ItemComparisonAjax from "./ajax/item-comparison-ajax";

export default class ItemDetailsModal extends React.Component<ItemDetailsModalProps, ItemDetailsModalState> {

    private ajax: ItemComparisonAjax;

    constructor(props: ItemDetailsModalProps) {
        super(props);

        this.state = {
            comparison_details: null,
            usable_sets: [],
            action_loading: false,
            loading: true,
            dark_charts: false,
            error_message: null,
            is_showing_expanded_details: false,
            secondary_actions: null,
        }

        this.ajax = serviceContainer().fetch(ItemComparisonAjax);
    }

    componentDidMount() {
        this.ajax.fetchChatComparisonData(this);
    }

    manageShowingExpandedDetails() {
        this.setState({
            is_showing_expanded_details: !this.state.is_showing_expanded_details
        }, () => {
            if (!this.state.is_showing_expanded_details) {
               return this.setState({ secondary_actions: null })
            }

            const secondaryAction ={
                secondary_button_disabled: false,
                secondary_button_label: 'Back to comparison',
                handle_action: this.manageShowingExpandedDetails.bind(this)
            }

            return this.setState({
                secondary_actions: secondaryAction,
            })
        })
    }

    buildTitle(): ReactNode | string {
        if (this.state.error_message !== null) {
            return "Uh oh. Something went wrong.";
        }

        if (this.state.comparison_details === null) {
            return "Loading comparison data ...";
        }

        return <ItemDetailsModalTitle itemToEquip={this.state.comparison_details.itemToEquip} />
    }

    render() {

        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.manage_modal}
                title={this.buildTitle()}
                large_modal={true}
                primary_button_disabled={this.state.action_loading}
                secondary_actions={this.state.secondary_actions}
            >
                {
                    this.state.loading ?
                        <LoadingProgressBar />
                    :

                        this.state.error_message !== null ?
                            <DangerAlert>
                                {this.state.error_message}
                            </DangerAlert>
                        : this.state.comparison_details !== null ?
                                <ItemView
                                    comparison_details={this.state.comparison_details}
                                    usable_sets={this.state.usable_sets}
                                    manage_showing_expanded_section={this.manageShowingExpandedDetails.bind(this)}
                                    is_showing_expanded_section={this.state.is_showing_expanded_details}
                                    manage_modal={this.props.manage_modal}
                                    set_success_message={this.props.set_success_message}
                                    update_inventory={this.props.update_inventory}
                                    is_automation_running={this.props.is_automation_running}
                                    is_dead={this.props.is_dead}
                                />
                        : null

                }
            </Dialogue>
        );
    }
}
