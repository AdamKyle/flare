import React, {ReactNode} from "react";
import {ChatItemComparisonProps} from "./types/chat-item-comparison-props";
import {ChatItemComparisonState} from "./types/chat-item-comparison-state";
import Dialogue from "../../ui/dialogue/dialogue";
import ChatItemComparisonModalTitle from "./chat-item-comparison-modal-title";
import ComponentLoading from "../../ui/loading/component-loading";
import ChatItemComparisonAjax from "./ajax/chat-item-comparison-ajax";
import {serviceContainer} from "../../../lib/containers/core-container";

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
                    this.state.loading ?
                        <ComponentLoading />
                    : <>
                        Put content here ...
                    </>
                }
            </Dialogue>
        );
    }
}
