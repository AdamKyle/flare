import React, { Fragment } from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import ExtraActionType from "../../../../lib/game/character/extra-action-type";

export type CharacterSheetStatHelpType =
    | "damage-stat"
    | "to-hit"
    | "class-bonus"
    | "fight-time-out"
    | "movement-time-out";

export default class CharacterSheetStatHelpModal extends React.Component<{
    is_open: boolean;
    manage_modal: () => void;
    type: CharacterSheetStatHelpType;
    extra_action_chance: ExtraActionType;
}> {
    title(): string {
        switch (this.props.type) {
            case "damage-stat":
                return "Damage Stat";
            case "to-hit":
                return "To Hit";
            case "class-bonus":
                return "Class Bonus";
            case "fight-time-out":
                return "Fight Time Out";
            case "movement-time-out":
                return "Movement Time Out";
        }
    }

    text(): string {
        switch (this.props.type) {
            case "damage-stat":
                return "Your class chooses the main stat your attacks scale from. The game uses your current total for that stat, including gear, enchantments, boons, class specialties, and other bonuses. Raise that stat and its bonuses to improve your attack damage. Use Show additional details to view the full damage breakdown.";
            case "to-hit":
                return "Your class chooses the stat used to land hits. The game compares your current total for that stat, with Accuracy for weapon attacks or Casting Accuracy for spells, against the monster’s Agility and Dodge. Raise this stat and the matching accuracy bonus to hit more often.";
            case "class-bonus":
                return "This is the chance for your class’s special extra action to happen. It starts at 5%, increases from your class skill bonus, and cannot go above 100%. It only works when the required item or condition shown below is met.";
            case "fight-time-out":
                return "This lowers the wait after normal fights. The normal wait starts at 10 seconds, this bonus reduces it, and it cannot go below 5 seconds. It does not reduce the 20 second wait when your character is dead. This bonus comes from trained skills that lower fight wait time.";
            case "movement-time-out":
                return "This lowers longer movement waits, such as teleporting or setting sail. It does not reduce normal walking or map traversal waits. The movement wait cannot go below 1 minute. This bonus comes from trained skills that lower movement wait time.";
        }
    }

    renderClassBonusDetails() {
        if (this.props.type !== "class-bonus") {
            return null;
        }

        return (
            <dl className="mt-4">
                <dt>Class Name</dt>
                <dd>{this.props.extra_action_chance.class_name}</dd>
                <dt>Type</dt>
                <dd>{this.props.extra_action_chance.type}</dd>
                <dt>Only</dt>
                <dd>{this.props.extra_action_chance.only}</dd>
                <dt>Has Item</dt>
                <dd>
                    {this.props.extra_action_chance.has_item ? "Yes" : "No"}
                </dd>
                {typeof this.props.extra_action_chance.amount !==
                "undefined" ? (
                    <Fragment>
                        <dt>Amount</dt>
                        <dd>{this.props.extra_action_chance.amount}</dd>
                    </Fragment>
                ) : null}
            </dl>
        );
    }

    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.manage_modal}
                title={this.title()}
            >
                <p>{this.text()}</p>
                {this.renderClassBonusDetails()}
            </Dialogue>
        );
    }
}
