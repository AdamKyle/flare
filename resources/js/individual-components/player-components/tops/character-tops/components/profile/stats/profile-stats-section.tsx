import React from "react";
import BasicCard from "../../../../../../../game/components/ui/cards/basic-card";
import TopsStatList from "../../../../shared/components/tops-stat-list";
import { formatTopsValue } from "../../../../shared/helpers/tops-format-value";
import {
    asTopsRecord,
    asTopsRecordList,
} from "../../../../shared/helpers/tops-value-helpers";
import TopsValue from "../../../../shared/types/tops-value";
import TopsStatListItem from "../../../../shared/types/tops-stat-list-item";
import ProfileStatsSectionProps from "../../../types/profile/stats/profile-stats-section-props";
import ProfileStatsSectionState from "../../../types/profile/stats/profile-stats-section-state";
import ProfileStatBreakdownModal from "./profile-stat-breakdown-modal";

export default class ProfileStatsSection extends React.Component<
    ProfileStatsSectionProps,
    ProfileStatsSectionState
> {
    constructor(props: ProfileStatsSectionProps) {
        super(props);

        this.state = {
            selectedStat: null,
        };
    }

    stats(): Record<string, TopsValue> {
        return this.props.stats ?? {};
    }

    openStat(stat: Record<string, TopsValue>): void {
        this.setState({
            selectedStat: stat,
        });
    }

    closeStat(): void {
        this.setState({
            selectedStat: null,
        });
    }

    statItems(values: Record<string, TopsValue>): TopsStatListItem[] {
        return [
            { label: "Strength", value: values.str },
            { label: "Durability", value: values.dur },
            { label: "Dexterity", value: values.dex },
            { label: "Charisma", value: values.chr },
            { label: "Intelligence", value: values.int },
            { label: "Agility", value: values.agi },
            { label: "Focus", value: values.focus },
            { label: "AC", value: values.ac },
        ];
    }

    combatItems(): TopsStatListItem[] {
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
        const resistances = asTopsRecord(this.stats().resistances);

        return Object.keys(resistances).map((key: string) => ({
            label: key.replaceAll("_", " "),
            value: resistances[key],
        }));
    }

    statBreakdownItems(): Record<string, TopsValue>[] {
        return Object.values(asTopsRecord(this.stats().stat_breakdown)).filter(
            (value: TopsValue): value is Record<string, TopsValue> =>
                value !== null &&
                typeof value === "object" &&
                !Array.isArray(value),
        );
    }

    renderBreakdownCard(stat: Record<string, TopsValue>, index: number) {
        return (
            <button
                key={String(stat.label ?? "stat") + index}
                type="button"
                className="rounded-sm border border-gray-200 p-4 text-left transition hover:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-700"
                onClick={() => this.openStat(stat)}
            >
                <span className="block text-sm font-semibold">
                    {stat.label ?? "Unknown Stat"}
                </span>
                <span className="mt-1 block text-2xl font-bold tabular-nums">
                    {formatTopsValue(stat.value)}
                </span>
            </button>
        );
    }

    render() {
        const stats = this.stats();
        const elementalAtonement = asTopsRecordList(stats.elemental_atonement);
        const statBreakdown = this.statBreakdownItems();

        return (
            <section className="grid gap-4 lg:grid-cols-2" aria-label="Stats">
                <BasicCard>
                    <h2 className="text-xl font-semibold">Base Stats</h2>
                    <div className="mt-4">
                        <TopsStatList
                            items={this.statItems(
                                asTopsRecord(stats.base_stats),
                            )}
                        />
                    </div>
                </BasicCard>
                <BasicCard>
                    <h2 className="text-xl font-semibold">Modified Stats</h2>
                    <div className="mt-4">
                        <TopsStatList
                            items={this.statItems(
                                asTopsRecord(stats.modded_stats),
                            )}
                        />
                    </div>
                </BasicCard>
                <BasicCard>
                    <h2 className="text-xl font-semibold">Combat Totals</h2>
                    <div className="mt-4">
                        <TopsStatList items={this.combatItems()} />
                    </div>
                </BasicCard>
                <BasicCard>
                    <h2 className="text-xl font-semibold">Resistances</h2>
                    <div className="mt-4">
                        <TopsStatList items={this.resistanceItems()} />
                    </div>
                </BasicCard>
                <BasicCard additionalClasses="lg:col-span-2">
                    <h2 className="text-xl font-semibold">
                        Read-only Breakdown
                    </h2>
                    <div className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        {statBreakdown.map(
                            (stat: Record<string, TopsValue>, index: number) =>
                                this.renderBreakdownCard(stat, index),
                        )}
                    </div>
                </BasicCard>
                {elementalAtonement.length > 0 ? (
                    <BasicCard additionalClasses="lg:col-span-2">
                        <h2 className="text-xl font-semibold">
                            Elemental Atonement
                        </h2>
                        <div className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            {elementalAtonement.map(
                                (
                                    row: Record<string, TopsValue>,
                                    index: number,
                                ) => (
                                    <div
                                        key={
                                            String(row.name ?? "atonement") +
                                            index
                                        }
                                        className="rounded-sm border border-gray-200 p-3 dark:border-gray-700"
                                    >
                                        <p className="font-semibold">
                                            {row.name ?? "Atonement"}
                                        </p>
                                        <p className="text-sm text-gray-700 dark:text-gray-300">
                                            {formatTopsValue(
                                                row.value ?? row.amount,
                                            )}
                                        </p>
                                    </div>
                                ),
                            )}
                        </div>
                    </BasicCard>
                ) : null}
                {this.state.selectedStat ? (
                    <ProfileStatBreakdownModal
                        stat={this.state.selectedStat}
                        onClose={() => this.closeStat()}
                    />
                ) : null}
            </section>
        );
    }
}
