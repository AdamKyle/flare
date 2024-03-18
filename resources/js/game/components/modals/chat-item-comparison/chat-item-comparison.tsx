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
        }

        this.ajax = serviceContainer().fetch(ChatItemComparisonAjax);
    }

    componentDidMount() {
        this.ajax.fetchChatComparisonData(this);
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
            >
                {
                    this.state.loading || this.state.comparison_details === null ?
                        <LoadingProgressBar />
                    :
                        <ItemView comparison_details={this.state.comparison_details}  />
                }
            </Dialogue>
        );
    }
}
