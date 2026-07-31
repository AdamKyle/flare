import React from "react";
import BasicCard from "../../../../../../../game/components/ui/cards/basic-card";
import ItemNameColorationText from "../../../../../../../game/components/items/item-name/item-name-coloration-text";
import InventoryDetails from "../../../../../../../game/lib/game/character-sheet/types/inventory/inventory-details";
import TopsEmptyState from "../../../../shared/components/tops-empty-state";
import { formatTopsValue } from "../../../../shared/helpers/tops-format-value";
import ProfileWornItemsProps from "../../../types/profile/equipment/profile-equipped-items-props";
import ProfileWornItemsState from "../../../types/profile/equipment/profile-equipped-items-state";
import InventoryUseDetails from "../../../../../../../game/sections/character-sheet/components/modals/inventory-item-details";

export default class ProfileWornItems extends React.Component<
    ProfileWornItemsProps,
    ProfileWornItemsState
> {
    constructor(props: ProfileWornItemsProps) {
        super(props);

        this.state = {
            selectedItem: null,
        };
    }

    openItem(item: InventoryDetails): void {
        this.setState({
            selectedItem: item,
        });
    }

    closeItem(): void {
        this.setState({
            selectedItem: null,
        });
    }

    renderItem(item: InventoryDetails) {
        return (
            <article
                key={String(item.position) + "-" + String(item.item_id)}
                className="rounded-sm border border-gray-200 p-4 dark:border-gray-700"
            >
                <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p className="text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">
                            {item.position ?? "Unknown Slot"}
                        </p>
                        <button
                            type="button"
                            onClick={() => this.openItem(item)}
                            className="text-left hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-regent-st-blue-400"
                        >
                            <ItemNameColorationText
                                custom_width={false}
                                item={item}
                            />
                        </button>
                    </div>
                    <p className="text-sm font-semibold">
                        Damage {formatTopsValue(item.base_damage)} / AC{" "}
                        {formatTopsValue(item.base_ac)} / Healing{" "}
                        {formatTopsValue(item.base_healing)}
                    </p>
                </div>
            </article>
        );
    }

    render() {
        const equipment = this.props.equipment;
        const items = equipment?.items ?? [];

        return (
            <BasicCard>
                <section aria-label="Read-only equipped items">
                    <h2 className="text-xl font-semibold">Worn Items</h2>
                    <p className="mt-1 text-sm text-gray-700 dark:text-gray-300">
                        {equipment?.source === "inventory_set"
                            ? "Active set"
                            : "Active inventory slots"}
                        {equipment?.set_name ? ": " + equipment.set_name : ""}
                    </p>
                    <div className="mt-4 grid gap-3">
                        {items.length === 0 ? (
                            <TopsEmptyState message="This character has no public equipped items to show." />
                        ) : (
                            items.map((item: InventoryDetails) =>
                                this.renderItem(item),
                            )
                        )}
                    </div>
                </section>

                {this.state.selectedItem !== null ? (
                    <InventoryUseDetails
                        is_open={true}
                        manage_modal={() => this.closeItem()}
                        character_id={this.props.character_id}
                        preloaded_item={this.state.selectedItem}
                    />
                ) : null}
            </BasicCard>
        );
    }
}
