import React from "react";
import Dialogue from "../../../ui/dialogue/dialogue";
import MoveModalProps from "../../../../lib/game/character-sheet/types/modal/move-modal-props";
import DropDown from "../../../ui/drop-down/drop-down";

export default class MoveItemModal extends React.Component<
    MoveModalProps,
    any
> {
    constructor(props: MoveModalProps) {
        super(props);

        this.state = {
            set_name: null,
            set_id: null,
        };
    }

    setName(setId: number) {
        this.setState({
            set_name: this.props.usable_sets.filter(
                (set) => set.id === setId,
            )[0].name,
            set_id: setId,
        });
    }

    move() {
        this.props.move_item(this.state.set_id);

        this.props.manage_modal();
    }

    buildDropDown() {
        return this.props.usable_sets.map((set) => {
            return {
                name: set.name,
                icon_class: "fas fa-shopping-bag",
                on_click: () => this.setName(set.id),
            };
        });
    }

    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.manage_modal}
                title={"Move to set"}
                secondary_actions={{
                    secondary_button_disabled: this.state.set_id === null,
                    secondary_button_label: "Move",
                    handle_action: this.move.bind(this),
                }}
            >
                <div
                    className={
                        "grid grid-cols-2 gap-2 max-h-[450px] lg:max-h-full overflow-y-scroll lg:overflow-y-auto"
                    }
                >
                    <div>
                        <h3 className="mb-3">Rules</h3>
                        <p className="mb-3">
                            You can move any item to any set from your
                            inventory, but if you plan to equip that set you
                            must follow the rules below.
                        </p>
                        <ul className="mb-3 list-disc ml-4">
                            <li>
                                <strong>Hands</strong>: 1 or 2 weapons for
                                hands, or 1 or 2 shields or 1 duel wielded
                                weapon (bow, hammer or stave). Guns, Fans,
                                Scratch Awls and Maces follow the same rules
                            </li>
                            <li>
                                <strong>Armour</strong>: 1 of each type, body,
                                head, leggings ...
                            </li>
                            <li>
                                <strong>Spells</strong>: Max of 2 regardless of
                                type.
                            </li>
                            <li>
                                <strong>Rings</strong>: Max of 2
                            </li>
                            <li>
                                <strong>Trinkets</strong>: Max of 1
                            </li>
                            <li>
                                <strong>Uniques (green items)</strong>: 1
                                unique, regardless of type.
                            </li>
                            <li>
                                <strong>Mythics (orange items)</strong>: 1
                                Mythic, if there is no Unique, regardless of
                                type.
                            </li>
                            <li>
                                <strong>Comsic (light purple items)</strong>: 1
                                Cosmic, if there is no Unique OR Mythic,
                                regardless of type.
                            </li>
                            <li>
                                <strong>Ancestral Items (purple items)</strong>:
                                1 Ancestral item only.
                            </li>
                        </ul>
                        <p className="mb-3">
                            The above rules only apply to characters who want to
                            equip the set, You may also use a set as a stash tab
                            with unlimited items.
                        </p>
                    </div>
                    <div>
                        <DropDown
                            menu_items={this.buildDropDown()}
                            button_title={
                                this.state.set_name !== null
                                    ? this.state.set_name
                                    : "Move to set"
                            }
                        />
                    </div>
                </div>
            </Dialogue>
        );
    }
}
