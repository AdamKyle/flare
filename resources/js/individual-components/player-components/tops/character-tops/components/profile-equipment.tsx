import React from "react";
import BasicCard from "../../../../../game/components/ui/cards/basic-card";
import TopsEmptyState from "../../shared/components/tops-empty-state";
import { formatTopsValue } from "../../shared/helpers/tops-format-value";
import ProfileSectionProps from "../types/profile-section-props";
import TopsValue from "../../shared/types/tops-value";
import {
    asTopsRecord,
    asTopsRecordList,
} from "../../shared/helpers/tops-value-helpers";

export default class ProfileEquipment extends React.Component<ProfileSectionProps> {
    modifierSummary(item: Record<string, TopsValue>): string {
        const statModifiers = asTopsRecord(item.stat_modifiers);
        const modifiers = Object.keys(statModifiers)
            .filter((key: string) => Number(statModifiers[key] ?? 0) !== 0)
            .map(
                (key: string) =>
                    key.toUpperCase() +
                    " " +
                    formatTopsValue(statModifiers[key]),
            );

        return modifiers.join(", ") || "—";
    }

    gemSummary(item: Record<string, TopsValue>): string {
        const attachedGems = asTopsRecordList(item.attached_gems)
            .map((gem: Record<string, TopsValue>) => gem.name)
            .filter((name: TopsValue | undefined) => typeof name === "string");

        return attachedGems.join(", ") || "—";
    }

    renderItem(item: Record<string, TopsValue>) {
        return (
            <article
                key={String(item.position) + "-" + String(item.name)}
                className="rounded-sm border border-gray-200 p-4 dark:border-gray-700"
            >
                <div className="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 className="font-semibold">
                            {item.name ?? "Unknown Item"}
                        </h3>
                        <p className="text-sm text-gray-700 dark:text-gray-300">
                            {item.position ?? "Unknown Position"} /{" "}
                            {item.type ?? "Unknown Type"}
                        </p>
                    </div>
                    <p className="text-sm font-semibold">
                        Damage {formatTopsValue(item.attack)} / AC{" "}
                        {formatTopsValue(item.ac)}
                    </p>
                </div>
                <dl className="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                    <div>
                        <dt className="font-semibold">Stat Modifiers</dt>
                        <dd>{this.modifierSummary(item)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Prefix / Suffix</dt>
                        <dd>
                            {item.prefix ?? "—"} / {item.suffix ?? "—"}
                        </dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Holy Stacks</dt>
                        <dd>{formatTopsValue(item.holy_stacks)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Attached Gems</dt>
                        <dd>{this.gemSummary(item)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold">Item Skill</dt>
                        <dd>{item.item_skill ?? "—"}</dd>
                    </div>
                </dl>
            </article>
        );
    }

    render() {
        const equipment = this.props.equipment ?? {};
        const items = asTopsRecordList(equipment.items);

        return (
            <BasicCard>
                <section aria-label="Equipment">
                    <h2 className="text-xl font-semibold">Equipment</h2>
                    <p className="mt-1 text-sm text-gray-700 dark:text-gray-300">
                        Source:{" "}
                        {equipment.source === "inventory_set"
                            ? "Equipped Inventory Set"
                            : "Equipped Inventory Slots"}
                        {equipment.set_name ? " / " + equipment.set_name : ""}
                    </p>
                    <div className="mt-4 grid gap-3">
                        {items.length === 0 ? (
                            <TopsEmptyState message="This character has no public equipped items to show." />
                        ) : (
                            items.map((item: Record<string, TopsValue>) =>
                                this.renderItem(item),
                            )
                        )}
                    </div>
                </section>
            </BasicCard>
        );
    }
}
