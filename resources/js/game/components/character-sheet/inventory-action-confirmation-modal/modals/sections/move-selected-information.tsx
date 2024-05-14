import React from "react";
import { SelectedItemsActionInformationProps } from "../../types/modals/sections/selected-items-action-information-props";
import DropDown from "../../../../ui/drop-down/drop-down";

export default class MoveSelectedInformation extends React.Component<
    SelectedItemsActionInformationProps,
    any
> {
    constructor(props: SelectedItemsActionInformationProps) {
        super(props);

        this.state = {
            set_name: "Select a set",
            set_id: null,
        };
    }

    renderSelectedItemNames() {
        return this.props.item_names.map((name) => {
            return <li>{name}</li>;
        });
    }

    setName(setId: number) {
        if (!this.props.usable_sets) {
            return;
        }

        if (!this.props.update_api_params) {
            return;
        }

        const setName = this.props.usable_sets.filter(
            (set) => set.id === setId,
        )[0].name;

        this.setState(
            {
                set_name: setName,
                set_id: setId,
            },
            () => {
                if (!this.props.usable_sets) {
                    return;
                }

                if (!this.props.update_api_params) {
                    return;
                }

                this.props.update_api_params({
                    set_name: setName,
                    set_id: setId,
                });
            },
        );
    }

    buildDropDown() {
        if (!this.props.usable_sets) {
            return [];
        }

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
            <>
                <div
                    className={
                        "grid grid-cols-2 gap-4 max-h-[450px] lg:max-h-full overflow-y-scroll lg:overflow-y-auto"
                    }
                >
                    <div>
                        <h3 className="mb-3">Movement Details</h3>
                        <p className="mb-3">
                            Are you sure you want to do this? This action will
                            move all selected items below.{" "}
                        </p>
                        <p className="mb-3">
                            Below, the list of items to move, you can select
                            what set to move it to. To the right are a set of
                            rules for making a set equitable.
                        </p>
                        <p className="mb-3">
                            Only non equipped sets may be chosen.
                        </p>
                        {this.props.usable_sets ? (
                            <DropDown
                                menu_items={this.buildDropDown()}
                                button_title={
                                    this.state.set_name !== null
                                        ? this.state.set_name
                                        : "Move to set"
                                }
                            />
                        ) : null}
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                        <span className="mb-3">
                            <strong>Items to Move</strong>
                        </span>
                        <ul className="my-3 pl-4 list-disc ml-4">
                            {this.renderSelectedItemNames()}
                        </ul>
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                    </div>
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
                </div>
            </>
        );
    }
}
