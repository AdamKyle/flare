import React, { Fragment } from "react";
import SuccessOutlineButton from "../../../game/components/ui/buttons/success-outline-button";
import GuideQuest from "./modals/guide-quest";
import { viewPortWatcher } from "../../../game/lib/view-port-watcher";
import GuideQuestListenerDefinition from "./event-listeners/guide-quest-listener-definition";
import { guideQuestServiceContainer } from "./container/guide-quest-container";
import GuideQuestListener from "./event-listeners/guide-quest-listener";
import GuideButtonProps from "./types/guide-button-props";
import GuideButtonState from "./types/guide-button-state";
import clsx from "clsx";
import CompletedGuideQuestListener from "./event-listeners/completed-guide-quest-listener";

export default class GuideButton extends React.Component<
    GuideButtonProps,
    GuideButtonState
> {
    private guideQuestListener: GuideQuestListenerDefinition;
    private guideQuestCompletedListener: GuideQuestListenerDefinition;

    constructor(props: GuideButtonProps) {
        super(props);

        this.state = {
            is_modal_open: false,
            show_button: true,
            show_guide_quest_completed: false,
            view_port: 0,
        };

        this.guideQuestListener =
            guideQuestServiceContainer().fetch(GuideQuestListener);
        this.guideQuestListener.initialize(this, this.props.user_id);

        this.guideQuestCompletedListener = guideQuestServiceContainer().fetch(
            CompletedGuideQuestListener,
        );
        this.guideQuestCompletedListener.initialize(this, this.props.user_id);

        this.guideQuestListener.register();
        this.guideQuestCompletedListener.register();
    }

    componentDidMount() {
        this.setState(
            {
                view_port: window.innerWidth,
            },
            () => {
                viewPortWatcher(this);
            },
        );

        setTimeout(
            () => {
                if (this.props.force_open_modal) {
                    this.setState({
                        is_modal_open: true,
                    });
                }
            },
            (import.meta as unknown as { env: { VITE_APP_ENV: string } }).env
                .VITE_APP_ENV === "production"
                ? 3500
                : 500,
        );

        this.guideQuestListener.listen();
        this.guideQuestCompletedListener.listen();
    }

    manageGuideQuestModal = () => {
        this.setState({
            is_modal_open: !this.state.is_modal_open,
        });
    };

    render() {
        if (!this.state.show_button) {
            return null;
        }

        return (
            <Fragment>
                <div className="relative">
                    <span
                        className={clsx(
                            "fa-stack absolute top-[-10px] left-[-15px]",
                            {
                                hidden: !this.state.show_guide_quest_completed,
                            },
                        )}
                    >
                        <i className="fas fa-circle fa-stack-2x text-red-700 dark:text-red-500 fa-beat"></i>
                        <i className="fas fa-exclamation fa-stack-1x text-yellow-500 dark:text-yello-700"></i>
                    </span>

                    <SuccessOutlineButton
                        button_label={"Guide Quests"}
                        on_click={this.manageGuideQuestModal}
                        additional_css={"mr-4"}
                    />
                </div>

                {this.state.is_modal_open ? (
                    <GuideQuest
                        is_open={this.state.is_modal_open}
                        manage_modal={this.manageGuideQuestModal}
                        user_id={this.props.user_id}
                        view_port={this.state.view_port}
                    />
                ) : null}
            </Fragment>
        );
    }
}
