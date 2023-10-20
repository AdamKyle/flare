import React, { Fragment } from "react";
import SuccessOutlineButton from "../../components/ui/buttons/success-outline-button";
import GuideQuest from "./modals/guide-quest";
import { viewPortWatcher } from "../../lib/view-port-watcher";

export default class GuideButton extends React.Component<any, any> {
    private guideQuestButton: any;

    constructor(props: any) {
        super(props);

        this.state = {
            is_modal_open: false,
            show_button: true,
            view_port: 0,
        };

        // @ts-ignore
        this.guideQuestButton = Echo.private(
            "guide-quest-button-" + this.props.user_id
        );
    }

    componentDidMount() {
        this.setState(
            {
                view_port: window.innerWidth,
            },
            () => {
                viewPortWatcher(this);
            }
        );

        const self = this;

        setTimeout(
            function () {
                if (self.props.force_open_modal) {
                    self.setState({
                        is_modal_open: true,
                    });
                }
            },
            process.env.APP_ENV === "production" ? 3500 : 500
        );

        // @ts-ignore
        this.guideQuestButton.listen(
            "Game.GuideQuests.Events.RemoveGuideQuestButton",
            (event: any) => {
                this.setState({
                    show_button: false,
                });
            }
        );
    }

    manageGuideQuestModal() {
        this.setState({
            is_modal_open: !this.state.is_modal_open,
        });
    }

    render() {
        if (!this.state.show_button) {
            return null;
        }

        return (
            <Fragment>
                <SuccessOutlineButton
                    button_label={"Guide Quests"}
                    on_click={this.manageGuideQuestModal.bind(this)}
                    additional_css={"mr-4"}
                />

                {this.state.is_modal_open ? (
                    <GuideQuest
                        is_open={this.state.is_modal_open}
                        manage_modal={this.manageGuideQuestModal.bind(this)}
                        user_id={this.props.user_id}
                        view_port={this.state.view_port}
                    />
                ) : null}
            </Fragment>
        );
    }
}
