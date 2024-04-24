import React from "react";
import ReviveProps from "./types/revive-props";
import PrimaryButton from "../../../../components/ui/buttons/primary-button";
import MonsterActionsManager from "../../../../lib/game/actions/smaller-actions-components/monster-actions-manager";

export default class Revive extends React.Component<ReviveProps, {}> {
    private monster: MonsterActionsManager;

    constructor(props: ReviveProps) {
        super(props);

        this.monster = new MonsterActionsManager(this);
    }

    revive() {
        if (typeof this.props.revive_call_back !== "undefined") {
            this.monster.revive(
                this.props.character_id,
                this.props.revive_call_back,
            );
        } else {
            this.monster.revive(this.props.character_id);
        }
    }

    render() {
        if (this.props.is_character_dead) {
            return (
                <div className="text-center my-4 lg:ml-[-140px]">
                    <PrimaryButton
                        button_label={"Revive"}
                        on_click={this.revive.bind(this)}
                        additional_css={"mb-4"}
                        disabled={!this.props.can_attack}
                    />
                    <p>You are dead. Please Revive.</p>
                </div>
            );
        }
    }
}
