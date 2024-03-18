import React, {ReactNode} from "react";
import {ChatItemComparisonProps} from "./types/chat-item-comparison-props";
import {ChatItemComparisonState} from "./types/chat-item-comparison-state";
import Dialogue from "../../ui/dialogue/dialogue";
import ChatItemComparisonModalTitle from "./chat-item-comparison-modal-title";
import ChatItemComparisonAjax from "./ajax/chat-item-comparison-ajax";
import {serviceContainer} from "../../../lib/containers/core-container";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import ItemView from "./item-view";

export default class ChatItemComparison extends React.Component<ChatItemComparisonProps, ChatItemComparisonState> {

    private ajax: ChatItemComparisonAjax;

    constructor(props: ChatItemComparisonProps) {
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

        this.ajax = serviceContainer().fetch(ChatItemComparisonAjax);
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

        return <ChatItemComparisonModalTitle itemToEquip={this.state.comparison_details.itemToEquip} />
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
                    this.state.loading || this.state.comparison_details === null ?
                        <LoadingProgressBar />
                    :
                        <ItemView
                            comparison_details={this.state.comparison_details}
                            usable_sets={this.state.usable_sets}
                            manage_showing_expanded_section={this.manageShowingExpandedDetails.bind(this)}
                            is_showing_expanded_section={this.state.is_showing_expanded_details}
                        />
                }
            </Dialogue>
        );
    }
}
