import React from "react";
import BasicCard from "../../../../../game/components/ui/cards/basic-card";
import TopsStatList from "../../shared/components/tops-stat-list";
import TopsEmptyState from "../../shared/components/tops-empty-state";
import ProfileSectionProps from "../types/profile-section-props";
import TopsStatListItem from "../../shared/types/tops-stat-list-item";
import TopsValue from "../../shared/types/tops-value";
import { asTopsRecord } from "../../shared/helpers/tops-value-helpers";

export default class ProfileStats extends React.Component<ProfileSectionProps> {
    stats(): Record<string, TopsValue> {
        return this.props.stats ?? {};
    }

    statBreakdown(): Record<string, TopsValue> {
        return asTopsRecord(this.stats().stat_breakdown);
    }

    resistances(): Record<string, TopsValue> {
        return asTopsRecord(this.stats().resistances);
    }

    statItems(
        values: Record<string, TopsValue>,
        modded: boolean = false,
    ): TopsStatListItem[] {
        const prefix = modded ? "Modded " : "";

        return [
            { label: prefix + "Strength", value: values?.str },
            { label: prefix + "Durability", value: values?.dur },
            { label: prefix + "Dexterity", value: values?.dex },
            { label: prefix + "Charisma", value: values?.chr },
            { label: prefix + "Intelligence", value: values?.int },
            { label: prefix + "Agility", value: values?.agi },
            { label: prefix + "Focus", value: values?.focus },
            { label: prefix + "AC", value: values?.ac },
        ];
    }

    baseStatItems(): TopsStatListItem[] {
        return this.statItems(asTopsRecord(this.stats().base_stats));
    }

    moddedStatItems(): TopsStatListItem[] {
        return this.statItems(asTopsRecord(this.stats().modded_stats), true);
    }

    combatStatItems(): TopsStatListItem[] {
        const stats = this.stats();

        return [
            { label: "Damage Stat", value: stats.damage_stat },
            { label: "Weapon Damage", value: stats.weapon_damage },
            { label: "Spell Damage", value: stats.spell_damage },
            { label: "Healing", value: stats.healing },
            { label: "AC", value: stats.ac },
            { label: "To-Hit", value: stats.to_hit },
        ];
    }

    resistanceItems(): TopsStatListItem[] {
        const resistances = this.resistances();

        return [
            { label: "Spell Evasion", value: resistances.spell_evasion },
            {
                label: "Artifact Annulment",
                value: resistances.artifact_annulment,
            },
        ];
    }

    statBreakdownItems(): TopsStatListItem[] {
        const statBreakdown = this.statBreakdown();

        return Object.keys(statBreakdown).map((key: string) => ({
            label: key,
            value: statBreakdown[key],
        }));
    }

    renderStatBreakdown() {
        if (this.statBreakdownItems().length === 0) {
            return (
                <TopsEmptyState message="Detailed stat breakdown is not available for this character yet." />
            );
        }

        return <TopsStatList items={this.statBreakdownItems()} />;
    }

    render() {
        return (
            <section className="grid gap-4 lg:grid-cols-2" aria-label="Stats">
                <BasicCard>
                    <h2 className="text-xl font-semibold">Base Stats</h2>
                    <div className="mt-4">
                        <TopsStatList items={this.baseStatItems()} />
                    </div>
                </BasicCard>
                <BasicCard>
                    <h2 className="text-xl font-semibold">Modded Stats</h2>
                    <div className="mt-4">
                        <TopsStatList items={this.moddedStatItems()} />
                    </div>
                </BasicCard>
                <BasicCard>
                    <h2 className="text-xl font-semibold">Combat Stats</h2>
                    <div className="mt-4">
                        <TopsStatList items={this.combatStatItems()} />
                    </div>
                </BasicCard>
                <BasicCard>
                    <h2 className="text-xl font-semibold">Resistances</h2>
                    <div className="mt-4">
                        <TopsStatList items={this.resistanceItems()} />
                    </div>
                </BasicCard>
                <BasicCard additionalClasses="lg:col-span-2">
                    <h2 className="text-xl font-semibold">Stat Breakdown</h2>
                    <div className="mt-4">{this.renderStatBreakdown()}</div>
                </BasicCard>
            </section>
        );
    }
}
