import React from "react";
import Dialogue from "../../../ui/dialogue/dialogue";
import { EquipPositions } from "../../../../lib/game/character-sheet/helpers/inventory/equip-positions";
import PrimaryButton from "../../../ui/buttons/primary-button";
import { capitalize } from "lodash";
import EquipModalProps from "../types/equip-modal-props";

export default class EquipModal extends React.Component<EquipModalProps, {}> {
    constructor(props: EquipModalProps) {
        super(props);
    }

    equip(type: string, position?: string) {
        this.props.equip_item(type, position);

        this.props.manage_modal();
    }

    renderEquipButtons() {
        const buttons = EquipPositions.getAllowedPositions(
            this.props.item_to_equip.type,
        );

        if (buttons === null) {
            return (
                <PrimaryButton
                    button_label={"Equip"}
                    on_click={() =>
                        this.equip(
                            this.props.item_to_equip.type,
                            this.props.item_to_equip.type,
                        )
                    }
                />
            );
        }

        const buttonArray = buttons.map((button) => {
            return (
                <PrimaryButton
                    button_label={capitalize(button.split("-").join(" "))}
                    on_click={() =>
                        this.equip(this.props.item_to_equip.type, button)
                    }
                />
            );
        });

        return <div className={"grid grid-cols-2 gap-2"}>{buttonArray}</div>;
    }

    replacesTwoHandedItem(): boolean {
        if (
            this.props.is_bow_equipped ||
            this.props.is_hammer_equipped ||
            this.props.is_stave_equipped
        ) {
            return ["hammer", "stave", "bow"].includes(
                this.props.item_to_equip.type,
            );
        }

        return false;
    }

    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.manage_modal}
                title={"Select Position"}
            >
                {EquipPositions.isTwoHanded(this.props.item_to_equip.type) ? (
                    <p className="mt-3 mb-3">
                        It doesn't matter which hand you select for this item,
                        as both hands will be used.
                    </p>
                ) : null}

                {EquipPositions.isArmour(this.props.item_to_equip.type) ? (
                    <p className="mt-3 mb-3">
                        This item has a default position already selected.
                        (Armour will never let you select the position)
                    </p>
                ) : null}

                {EquipPositions.isArtifact(this.props.item_to_equip.type) ? (
                    <p className="mt-3 mb-3">
                        This item has a default position already selected.
                        (Ancestral [artifacts] will never let you select the
                        position)
                    </p>
                ) : null}

                {this.replacesTwoHandedItem() ? (
                    <p className="mt-3 mb-3">
                        Equipping this item will remove your two handed equipped
                        item.
                    </p>
                ) : null}

                {EquipPositions.isTrinket(this.props.item_to_equip.type) ? (
                    <p className="mt-3 mb-3">
                        Trinkets have their own dedicated slot. Players may only
                        equip one trinket
                    </p>
                ) : null}

                {this.renderEquipButtons()}
            </Dialogue>
        );
    }
}
