import React from "react";
import BasicCard from "../../../../../game/components/ui/cards/basic-card";
import TopsStatList from "../../shared/components/tops-stat-list";
import TopsEmptyState from "../../shared/components/tops-empty-state";
import ProfileSectionProps from "../types/profile-section-props";
import TopsValue from "../../shared/types/tops-value";
import {
    asTopsRecord,
    asTopsRecordList,
} from "../../shared/helpers/tops-value-helpers";
import TopsStatListItem from "../../shared/types/tops-stat-list-item";

export default class ProfileFactions extends React.Component<ProfileSectionProps> {
    factionItems(faction: Record<string, TopsValue>): TopsStatListItem[] {
        return [
            { label: "Current Level", value: faction.current_level },
            { label: "Current Points", value: faction.current_points },
            { label: "Points Needed", value: faction.points_needed },
            { label: "Maxed", value: faction.maxed ? "Yes" : "No" },
            { label: "Title", value: faction.title },
        ];
    }

    npcItems(npc: Record<string, TopsValue>): TopsStatListItem[] {
        return [
            { label: "Current Level", value: npc.current_level },
            { label: "Max Level", value: npc.max_level },
            { label: "Next Level Fame", value: npc.next_level_fame },
            {
                label: "Currently Helping",
                value: npc.currently_helping ? "Yes" : "No",
            },
        ];
    }

    automationSummaryItems(
        automationSummary: Record<string, TopsValue>,
    ): TopsStatListItem[] {
        return [
            {
                label: "Automation Count",
                value: automationSummary.count,
            },
            {
                label: "Latest Action",
                value: automationSummary.latest_action,
            },
            {
                label: "Latest Outcome",
                value: automationSummary.latest_outcome,
            },
        ];
    }

    renderFaction(faction: Record<string, TopsValue>, index: number) {
        return (
            <article
                key={String(faction.map ?? "faction") + index}
                className="rounded-sm border border-gray-200 p-3 dark:border-gray-700"
            >
                <h3 className="font-semibold">
                    {faction.map ?? "Unknown Plane"}
                </h3>
                <TopsStatList items={this.factionItems(faction)} />
            </article>
        );
    }

    renderNpc(npc: Record<string, TopsValue>, index: number) {
        return (
            <article
                key={String(npc.npc_name ?? "npc") + index}
                className="rounded-sm border border-gray-200 p-3 dark:border-gray-700"
            >
                <h3 className="font-semibold">
                    {npc.npc_name ?? "Unknown NPC"}
                </h3>
                <TopsStatList items={this.npcItems(npc)} />
            </article>
        );
    }

    render() {
        const factions = this.props.factions ?? {};
        const factionRows = asTopsRecordList(factions.factions);
        const npcRows = asTopsRecordList(factions.npcs);
        const automationSummary = asTopsRecord(factions.automation_summary);

        return (
            <section
                className="grid gap-4 lg:grid-cols-2"
                aria-label="Factions and Fame"
            >
                <BasicCard>
                    <h2 className="text-xl font-semibold">
                        Faction Progression
                    </h2>
                    <div className="mt-4 grid gap-3">
                        {factionRows.length === 0 ? (
                            <TopsEmptyState message="No faction progression is available." />
                        ) : (
                            factionRows.map(
                                (
                                    faction: Record<string, TopsValue>,
                                    index: number,
                                ) => this.renderFaction(faction, index),
                            )
                        )}
                    </div>
                </BasicCard>
                <BasicCard>
                    <h2 className="text-xl font-semibold">NPC Loyalty</h2>
                    <div className="mt-4 grid gap-3">
                        {npcRows.length === 0 ? (
                            <TopsEmptyState message="No NPC loyalty records are available." />
                        ) : (
                            npcRows.map(
                                (
                                    npc: Record<string, TopsValue>,
                                    index: number,
                                ) => this.renderNpc(npc, index),
                            )
                        )}
                    </div>
                </BasicCard>
                <BasicCard additionalClasses="lg:col-span-2">
                    <h2 className="text-xl font-semibold">
                        Automation Summary
                    </h2>
                    <div className="mt-4">
                        <TopsStatList
                            items={this.automationSummaryItems(
                                automationSummary,
                            )}
                        />
                    </div>
                </BasicCard>
            </section>
        );
    }
}
