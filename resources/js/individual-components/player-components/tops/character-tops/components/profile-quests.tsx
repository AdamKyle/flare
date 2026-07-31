import React from "react";
import BasicCard from "../../../../../game/components/ui/cards/basic-card";
import TopsStatList from "../../shared/components/tops-stat-list";
import TopsEmptyState from "../../shared/components/tops-empty-state";
import ProfileSectionProps from "../types/profile-section-props";
import TopsValue from "../../shared/types/tops-value";
import { asTopsRecordList } from "../../shared/helpers/tops-value-helpers";
import TopsStatListItem from "../../shared/types/tops-stat-list-item";

export default class ProfileQuests extends React.Component<ProfileSectionProps> {
    questItems(): TopsStatListItem[] {
        const quests = this.props.quests ?? {};

        return [
            {
                label: "Completed Quests",
                value: quests.completed_quest_count,
            },
            {
                label: "Completed Guide Quests",
                value: quests.completed_guide_quest_count,
            },
        ];
    }

    renderQuestList(title: string, quests: Record<string, TopsValue>[]) {
        return (
            <BasicCard>
                <h2 className="text-xl font-semibold">{title}</h2>
                <div className="mt-4 grid gap-2">
                    {quests.length === 0 ? (
                        <TopsEmptyState
                            message={
                                "No " + title.toLowerCase() + " are available."
                            }
                        />
                    ) : (
                        quests.map((quest: Record<string, TopsValue>) => (
                            <div
                                key={
                                    String(quest.id) + "-" + String(quest.name)
                                }
                                className="rounded-sm border border-gray-200 p-3 dark:border-gray-700"
                            >
                                {quest.name ?? "Unknown Quest"}
                            </div>
                        ))
                    )}
                </div>
            </BasicCard>
        );
    }

    render() {
        const quests = this.props.quests ?? {};

        return (
            <section className="grid gap-4 lg:grid-cols-2" aria-label="Quests">
                <BasicCard additionalClasses="lg:col-span-2">
                    <h2 className="text-xl font-semibold">Quest Counts</h2>
                    <div className="mt-4">
                        <TopsStatList items={this.questItems()} />
                    </div>
                </BasicCard>
                {this.renderQuestList(
                    "Completed Quests",
                    asTopsRecordList(quests.completed_quests),
                )}
                {this.renderQuestList(
                    "Completed Guide Quests",
                    asTopsRecordList(quests.completed_guide_quests),
                )}
            </section>
        );
    }
}
