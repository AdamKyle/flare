import React from "react";
import HelpDialogue from "../../../components/ui/dialogue/help-dialogue";

export default class TeleportHelpModal extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <HelpDialogue
                is_open={true}
                manage_modal={this.props.manage_modal}
                title="Timeout Help"
            >
                <p className="my-2">
                    When it comes to the teleport timeout, you have a skill
                    called{" "}
                    <a href="/information/skill-information" target="_blank">
                        Quick Feet <i className="fas fa-external-link-alt"></i>
                    </a>
                    , which if raised over time, will reduce the movement time
                    out of teleporting down from the current value to 1 minute,
                    regardless of distance.
                </p>
                <p>
                    You can find this on your character sheet, under Skills. You
                    can sacrifice a % of your XP from monsters in order to level
                    the skill over time, by clicking train on Quick Feet and
                    then selecting the amount of XP to sacrifice between
                    10-100%.
                </p>
            </HelpDialogue>
        );
    }
}
