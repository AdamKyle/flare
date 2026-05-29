import React, { Fragment } from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import ExtraActionType from "../../../../lib/game/character/extra-action-type";
import InventoryUseDetails from "./inventory-item-details";
import ItemNameColorationButton from "../../../../components/items/item-name/item-name-coloration-button";

export type CharacterSheetStatHelpType =
    | "damage-stat"
    | "to-hit"
    | "class-bonus"
    | "fight-time-out"
    | "movement-time-out";

interface CharacterSheetStatHelpModalProps {
    is_open: boolean;
    manage_modal: () => void;
    type: CharacterSheetStatHelpType;
    extra_action_chance: ExtraActionType;
    character_id: number;
}

interface CharacterSheetStatHelpModalState {
    item_id: number | null;
}

export default class CharacterSheetStatHelpModal extends React.Component<
    CharacterSheetStatHelpModalProps,
    CharacterSheetStatHelpModalState
> {
    constructor(props: CharacterSheetStatHelpModalProps) {
        super(props);

        this.state = {
            item_id: null,
        };
    }

    manageItemDetails(
        item:
            | {
                  item_id: number;
              }
            | number
            | null = null,
    ) {
        this.setState({
            item_id:
                typeof item === "number" || item === null ? item : item.item_id,
        });
    }

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

    renderSpecial() {
        return (
            <a
                href={`/information/class/${this.props.extra_action_chance.class_id}`}
                target="_blank"
            >
                {this.props.extra_action_chance.type}{" "}
                <i className="fas fa-external-link-alt"></i>
            </a>
        );
    }

    renderClassWeapons() {
        if (this.props.extra_action_chance.class_weapons.length === 0) {
            return "None";
        }

        return this.props.extra_action_chance.class_weapons.join(", ");
    }

    renderEquippedClassItems() {
        if (this.props.extra_action_chance.equipped_class_items.length === 0) {
            return "None";
        }

        return (
            <ul>
                {this.props.extra_action_chance.equipped_class_items.map(
                    (item) => {
                        return (
                            <li key={item.item_id} className={"text-center"}>
                                <ItemNameColorationButton
                                    item={item as any}
                                    on_click={this.manageItemDetails.bind(this)}
                                />{" "}
                                <span className={"italic"}>({item.type})</span>
                            </li>
                        );
                    },
                )}
            </ul>
        );
    }

    renderClassBonusDetails() {
        if (this.props.type !== "class-bonus") {
            return null;
        }

        return (
            <Fragment>
                <dl className="mt-4">
                    <dt>Class</dt>
                    <dd>{this.props.extra_action_chance.class_name}</dd>
                    <dt>Special</dt>
                    <dd>{this.renderSpecial()}</dd>
                    <dt>Attack Type Needed*</dt>
                    <dd>{this.props.extra_action_chance.attack_type}</dd>
                    <dt>Class Weapons</dt>
                    <dd>{this.renderClassWeapons()}</dd>
                    <dt>Required Equipped Item*</dt>
                    <dd>{this.props.extra_action_chance.only}</dd>
                    <dt>Has Required Item*</dt>
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
                    <dt>Items Equipped For Class</dt>
                    <dd>{this.renderEquippedClassItems()}</dd>
                </dl>
                <div className="mt-4 text-sm">
                    <p>
                        * (Attack Type Needed) The attack type you need to do
                        while having the required item equipped to auto proc the
                        class special when fighting, either manually or through
                        automation.
                    </p>
                    <p>
                        * (Required Equipped Item) Indicates the required item
                        you must have equipped for the class special to proc.
                    </p>
                    <p>
                        * (Has Required Item) Indicates if you have the required
                        item equipped for the class special to proc.
                    </p>
                </div>
                {this.state.item_id !== null ? (
                    <InventoryUseDetails
                        character_id={this.props.character_id}
                        item_id={this.state.item_id}
                        is_open={this.state.item_id !== null}
                        manage_modal={this.manageItemDetails.bind(this, null)}
                    />
                ) : null}
            </Fragment>
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
