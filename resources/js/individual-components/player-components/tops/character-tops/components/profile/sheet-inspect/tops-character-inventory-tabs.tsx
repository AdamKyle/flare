import React from "react";
import BasicCard from "../../../../../../../game/components/ui/cards/basic-card";
import ItemNameColorationText from "../../../../../../../game/components/items/item-name/item-name-coloration-text";

export default function TopsCharacterInventoryTabs({
    profile,
    on_item_select,
}: {
    profile: any;
    on_item_select: (item: any) => void;
}) {
    const equipment = profile.equipment ?? {};
    const items = equipment.items ?? [];

    return (
        <BasicCard>
            <h3 className="text-base font-semibold text-gray-900 dark:text-gray-100">
                Equipped Items
            </h3>
            {items.length === 0 ? (
                <p className="mt-4 text-sm text-gray-700 dark:text-gray-300">
                    None
                </p>
            ) : (
                <dl className="mt-4 grid gap-2">
                    {items.map((item: any, index: number) => (
                        <div
                            key={`${item.slot_id ?? item.item_id}-${index}`}
                            className="grid gap-1 border-b border-gray-200 pb-2 last:border-b-0 dark:border-gray-700 sm:grid-cols-[140px_minmax(0,1fr)]"
                        >
                            <dt className="text-sm font-semibold text-gray-600 dark:text-gray-400">
                                {item.position ?? item.type ?? "Equipped"}
                            </dt>
                            <dd>
                                <button
                                    type="button"
                                    className="text-left focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    onClick={() => on_item_select(item)}
                                >
                                    <ItemNameColorationText
                                        item={{
                                            name:
                                                item.item_name ??
                                                item.name ??
                                                "Unknown item",
                                            type: item.type ?? "item",
                                            affix_count:
                                                item.affix_count ??
                                                item.attached_affixes_count ??
                                                0,
                                            is_unique: item.is_unique ?? false,
                                            is_mythic: item.is_mythic ?? false,
                                            is_cosmic: item.is_cosmic ?? false,
                                            holy_stacks_applied:
                                                item.holy_stacks_applied ?? 0,
                                        }}
                                        custom_width={false}
                                        additional_css=""
                                    />
                                </button>
                            </dd>
                        </div>
                    ))}
                </dl>
            )}
        </BasicCard>
    );
}
