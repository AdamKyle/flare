import React from "react";
import BasicCard from "../../../../../../../game/components/ui/cards/basic-card";
import TopsEmptyState from "../../../../shared/components/tops-empty-state";
import { formatTopsValue } from "../../../../shared/helpers/tops-format-value";
import { asTopsRecordList } from "../../../../shared/helpers/tops-value-helpers";
import TopsValue from "../../../../shared/types/tops-value";
import ProfileQuestsSectionProps from "../../../types/profile/quests/profile-quests-section-props";

export default class ProfileQuestsSection extends React.Component<ProfileQuestsSectionProps> {
    renderQuestList(title: string, quests: Record<string, TopsValue>[]) {
        return (
            <BasicCard>
                <h2 className="text-xl font-semibold">{title}</h2>
                <div className="mt-4 max-h-96 overflow-y-auto pr-1">
                    {quests.length === 0 ? (
                        <TopsEmptyState message="No public quest completions are available." />
                    ) : (
                        <ul className="grid gap-2">
                            {quests.map(
                                (
                                    quest: Record<string, TopsValue>,
                                    index: number,
                                ) => (
                                    <li
                                        key={String(quest.id ?? title) + index}
                                        className="rounded-sm border border-gray-200 p-3 dark:border-gray-700"
                                    >
                                        {quest.name ?? "Unknown Quest"}
                                    </li>
                                ),
                            )}
                        </ul>
                    )}
                </div>
            </BasicCard>
        );
    }

    render() {
        const quests = this.props.quests ?? {};
        const completedQuests = asTopsRecordList(quests.completed_quests);
        const completedGuideQuests = asTopsRecordList(
            quests.completed_guide_quests,
        );

        return (
            <section
                className="grid gap-4 lg:grid-cols-2"
                aria-label="Completed quests"
            >
                <BasicCard additionalClasses="lg:col-span-2">
                    <h2 className="text-xl font-semibold">Quest Summary</h2>
                    <dl className="mt-4 grid gap-3 sm:grid-cols-2">
                        <div className="rounded-sm border border-gray-200 p-3 dark:border-gray-700">
                            <dt className="text-sm font-semibold">
                                Completed Quests
                            </dt>
                            <dd className="text-2xl font-bold tabular-nums">
                                {formatTopsValue(quests.completed_quest_count)}
                            </dd>
                        </div>
                        <div className="rounded-sm border border-gray-200 p-3 dark:border-gray-700">
                            <dt className="text-sm font-semibold">
                                Completed Guide Quests
                            </dt>
                            <dd className="text-2xl font-bold tabular-nums">
                                {formatTopsValue(
                                    quests.completed_guide_quest_count,
                                )}
                            </dd>
                        </div>
                    </dl>
                </BasicCard>
                {this.renderQuestList("Completed Quests", completedQuests)}
                {this.renderQuestList(
                    "Completed Guide Quests",
                    completedGuideQuests,
                )}
            </section>
        );
    }
}
