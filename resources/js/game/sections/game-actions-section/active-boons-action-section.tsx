import React from "react";
import OrangeButton from "../../components/ui/buttons/orange-button";
import CharacterActiveBoons from "../character-sheet/components/character-active-boons";
import ActiveBoonsActionsSectionProps from "./types/active-boons-actions-section-props";
import ActiveBoonsActionsSectionState from "./types/active-boons-actions-section-state";
import BasicCard from "../../components/ui/cards/basic-card";
import DangerButton from "../../components/ui/buttons/danger-button";

export default class ActiveBoonsActionSection extends React.Component<
    ActiveBoonsActionsSectionProps,
    ActiveBoonsActionsSectionState
> {
    constructor(props: ActiveBoonsActionsSectionProps) {
        super(props);

        this.state = {
            viewing_active_boons: false,
        };
    }

    manageViewingActiveBoons() {
        this.setState({
            viewing_active_boons: !this.state.viewing_active_boons,
        });
    }

    render() {
        return (
            <>
                <div className={"mb-4 mt-[-20px] text-center"}>
                    {!this.state.viewing_active_boons ? (
                        <OrangeButton
                            button_label={"Active Boons"}
                            on_click={this.manageViewingActiveBoons.bind(this)}
                        />
                    ) : (
                        <DangerButton
                            button_label={"Close Active Boons"}
                            on_click={this.manageViewingActiveBoons.bind(this)}
                        />
                    )}
                </div>

                {this.state.viewing_active_boons ? (
                    <BasicCard additionalClasses={"mb-4"}>
                        <CharacterActiveBoons
                            character_id={this.props.character_id}
                            finished_loading={true}
                        />
                    </BasicCard>
                ) : null}
            </>
        );
    }
}
