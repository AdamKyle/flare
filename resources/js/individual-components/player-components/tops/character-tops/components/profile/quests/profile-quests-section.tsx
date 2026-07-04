import React from "react";
import Select, { SingleValue } from "react-select";
import BasicCard from "../../../../../../../game/components/ui/cards/basic-card";
import Tabs from "../../../../../../../game/components/ui/tabs/tabs";
import TabPanel from "../../../../../../../game/components/ui/tabs/tab-panel";
import TopsEmptyState from "../../../../shared/components/tops-empty-state";
import { formatTopsValue } from "../../../../shared/helpers/tops-format-value";
import { asTopsRecordList } from "../../../../shared/helpers/tops-value-helpers";
import TopsValue from "../../../../shared/types/tops-value";
import ProfileQuestsSectionProps from "../../../types/profile/quests/profile-quests-section-props";
import TopsChartCard from "../sheet-inspect/tops-chart-card";
import { formatLocalDateTime } from "../../../../../../../game/lib/game/format-local-date";

const QUEST_TABS = [
    { key: "quests", name: "Quests" },
    { key: "guide-quests", name: "Guide Quests" },
];
const perPage = 10;

type Option = {
    label: string;
    value: string;
};

function readableLabel(value: string): string {
    return value
        .replace(/_/g, " ")
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function DetailRows({ rows }: { rows: Record<string, TopsValue> }) {
    const keys = Object.keys(rows).filter((key) => {
        const value = rows[key];

        return value !== null && typeof value !== "undefined" && value !== "";
    });

    if (keys.length === 0) {
        return null;
    }

    return (
        <dl className="grid gap-3 sm:grid-cols-2">
            {keys.map((key: string) => (
                <div key={key}>
                    <dt className="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        {readableLabel(key)}
                    </dt>
                    <dd className="text-gray-900 dark:text-gray-100">
                        {key.includes("date") || key.endsWith("_at")
                            ? formatLocalDateTime(rows[key])
                            : formatTopsValue(rows[key])}
                    </dd>
                </div>
            ))}
        </dl>
    );
}

function QuestDetailModal({
    quest,
    onClose,
}: {
    quest: Record<string, TopsValue>;
    onClose: () => void;
}) {
    React.useEffect(() => {
        const handler = (event: KeyboardEvent) => {
            if (event.key === "Escape") {
                onClose();
            }
        };

        document.addEventListener("keydown", handler);

        return () => document.removeEventListener("keydown", handler);
    }, [onClose]);

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="quest-detail-title"
        >
            <div className="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-sm bg-white p-6 shadow-lg dark:bg-gray-800 dark:text-gray-100">
                <div className="flex items-start justify-between gap-4">
                    <h2
                        id="quest-detail-title"
                        className="text-xl font-semibold"
                    >
                        {quest.name ?? "Quest Details"}
                    </h2>
                    <button
                        type="button"
                        className="rounded-sm border border-gray-300 px-3 py-1 text-sm font-semibold dark:border-gray-600"
                        onClick={onClose}
                    >
                        Close
                    </button>
                </div>
                <div className="mt-4 space-y-4">
                    {quest.before_completion_description ? (
                        <section>
                            <h3 className="font-semibold">About</h3>
                            <p className="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                {quest.before_completion_description}
                            </p>
                        </section>
                    ) : null}
                    {quest.after_completion_description ? (
                        <section>
                            <h3 className="font-semibold">Completion</h3>
                            <p className="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                {quest.after_completion_description}
                            </p>
                        </section>
                    ) : null}
                    {quest.intro_text || quest.instructions ? (
                        <section>
                            <h3 className="font-semibold">Instructions</h3>
                            <p className="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                {quest.intro_text ?? quest.instructions}
                            </p>
                            {quest.desktop_instructions ? (
                                <p className="mt-2 text-sm text-gray-700 dark:text-gray-300">
                                    {quest.desktop_instructions}
                                </p>
                            ) : null}
                            {quest.mobile_instructions ? (
                                <p className="mt-2 text-sm text-gray-700 dark:text-gray-300">
                                    {quest.mobile_instructions}
                                </p>
                            ) : null}
                        </section>
                    ) : null}
                    <DetailRows
                        rows={{
                            completed_at: quest.completed_at,
                            plane: quest.plane,
                            faction_plane: quest.faction_plane,
                            only_for_event: quest.only_for_event,
                            reincarnated_times: quest.reincarnated_times,
                            required_faction_level:
                                quest.required_faction_level,
                            required_fame_level: quest.required_fame_level,
                            ...((quest.requirements as Record<
                                string,
                                TopsValue
                            > | null) ?? {}),
                        }}
                    />
                    <section>
                        <h3 className="mb-2 font-semibold">Rewards</h3>
                        <DetailRows
                            rows={
                                ((quest.rewards as Record<
                                    string,
                                    TopsValue
                                > | null) ?? {}) as Record<string, TopsValue>
                            }
                        />
                    </section>
                </div>
            </div>
        </div>
    );
}

export default class ProfileQuestsSection extends React.Component<
    ProfileQuestsSectionProps,
    {
        selectedQuest: Record<string, TopsValue> | null;
        questSearch: string;
        guideQuestSearch: string;
        selectedPlane: Option | null;
        questPage: number;
        guideQuestPage: number;
    }
> {
    state = {
        selectedQuest: null,
        questSearch: "",
        guideQuestSearch: "",
        selectedPlane: null,
        questPage: 1,
        guideQuestPage: 1,
    };

    planeOptions(quests: Record<string, TopsValue>[]): Option[] {
        return quests
            .map((quest: Record<string, TopsValue>) =>
                String(quest.plane ?? ""),
            )
            .filter((plane: string) => plane !== "")
            .filter(
                (plane: string, index: number, planes: string[]) =>
                    planes.indexOf(plane) === index,
            )
            .sort()
            .map((plane: string) => ({ label: plane, value: plane }));
    }

    filteredQuests(quests: Record<string, TopsValue>[]) {
        const search = this.state.questSearch.toLowerCase();

        return quests.filter((quest: Record<string, TopsValue>) => {
            const matchesSearch = String(quest.name ?? "")
                .toLowerCase()
                .includes(search);
            const matchesPlane =
                this.state.selectedPlane === null ||
                quest.plane === this.state.selectedPlane.value;

            return matchesSearch && matchesPlane;
        });
    }

    filteredGuideQuests(quests: Record<string, TopsValue>[]) {
        const search = this.state.guideQuestSearch.toLowerCase();

        return quests.filter((quest: Record<string, TopsValue>) =>
            String(quest.name ?? "")
                .toLowerCase()
                .includes(search),
        );
    }

    paginatedRows(rows: Record<string, TopsValue>[], page: number) {
        const start = (page - 1) * perPage;

        return rows.slice(start, start + perPage);
    }

    totalPages(rows: Record<string, TopsValue>[]) {
        return Math.max(1, Math.ceil(rows.length / perPage));
    }

    renderPagination(
        page: number,
        rows: Record<string, TopsValue>[],
        onChange: (page: number) => void,
    ) {
        const totalPages = this.totalPages(rows);

        return (
            <div className="mt-4 flex flex-col gap-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                <span className="text-gray-600 dark:text-gray-400">
                    Page {page} of {totalPages}
                </span>
                <div className="flex gap-2">
                    <button
                        type="button"
                        className="rounded-sm border border-gray-300 px-3 py-1 font-semibold disabled:opacity-50 dark:border-gray-600"
                        disabled={page === 1}
                        onClick={() => onChange(Math.max(1, page - 1))}
                    >
                        Previous
                    </button>
                    <button
                        type="button"
                        className="rounded-sm border border-gray-300 px-3 py-1 font-semibold disabled:opacity-50 dark:border-gray-600"
                        disabled={page === totalPages}
                        onClick={() => onChange(Math.min(totalPages, page + 1))}
                    >
                        Next
                    </button>
                </div>
            </div>
        );
    }

    renderQuestRows(rows: Record<string, TopsValue>[]) {
        if (rows.length === 0) {
            return (
                <TopsEmptyState message="No public quest completions match these filters." />
            );
        }

        return (
            <ul className="grid gap-2">
                {rows.map((quest: Record<string, TopsValue>, index: number) => (
                    <li
                        key={String(quest.id ?? quest.name) + index}
                        className="rounded-sm border border-gray-200 p-3 dark:border-gray-700"
                    >
                        <button
                            type="button"
                            className="text-left font-semibold text-regent-st-blue-700 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-regent-st-blue-400 dark:text-regent-st-blue-300"
                            onClick={() =>
                                this.setState({ selectedQuest: quest })
                            }
                        >
                            {quest.name ?? "Unknown Quest"}
                        </button>
                        <dl className="mt-2 grid gap-2 text-sm sm:grid-cols-2">
                            <div>
                                <dt className="font-semibold text-gray-700 dark:text-gray-300">
                                    Completed
                                </dt>
                                <dd>
                                    {formatLocalDateTime(quest.completed_at)}
                                </dd>
                            </div>
                            {quest.plane ? (
                                <div>
                                    <dt className="font-semibold text-gray-700 dark:text-gray-300">
                                        Plane
                                    </dt>
                                    <dd>{String(quest.plane)}</dd>
                                </div>
                            ) : null}
                        </dl>
                    </li>
                ))}
            </ul>
        );
    }

    renderQuestsTab(quests: Record<string, TopsValue>[]) {
        const filteredRows = this.filteredQuests(quests);
        const visibleRows = this.paginatedRows(
            filteredRows,
            this.state.questPage,
        );
        const planes = this.planeOptions(quests);

        return (
            <div className="space-y-4">
                <div className="grid gap-3 md:grid-cols-2">
                    <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Search Quests
                        <input
                            type="search"
                            className="mt-1 w-full rounded-sm border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            value={this.state.questSearch}
                            onChange={(event) =>
                                this.setState({
                                    questSearch: event.target.value,
                                    questPage: 1,
                                })
                            }
                        />
                    </label>
                    <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Plane
                        <Select
                            className="mt-1 text-sm"
                            classNamePrefix="react-select"
                            options={planes}
                            isClearable={true}
                            value={this.state.selectedPlane}
                            onChange={(option: SingleValue<Option>) =>
                                this.setState({
                                    selectedPlane: option,
                                    questPage: 1,
                                })
                            }
                            aria-label="Filter completed quests by plane"
                            menuPortalTarget={document.body}
                        />
                    </label>
                </div>
                {this.renderQuestRows(visibleRows)}
                {this.renderPagination(
                    this.state.questPage,
                    filteredRows,
                    (page: number) => this.setState({ questPage: page }),
                )}
            </div>
        );
    }

    renderGuideQuestsTab(quests: Record<string, TopsValue>[]) {
        const filteredRows = this.filteredGuideQuests(quests);
        const visibleRows = this.paginatedRows(
            filteredRows,
            this.state.guideQuestPage,
        );

        return (
            <div className="space-y-4">
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Search Guide Quests
                    <input
                        type="search"
                        className="mt-1 w-full rounded-sm border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        value={this.state.guideQuestSearch}
                        onChange={(event) =>
                            this.setState({
                                guideQuestSearch: event.target.value,
                                guideQuestPage: 1,
                            })
                        }
                    />
                </label>
                {this.renderQuestRows(visibleRows)}
                {this.renderPagination(
                    this.state.guideQuestPage,
                    filteredRows,
                    (page: number) => this.setState({ guideQuestPage: page }),
                )}
            </div>
        );
    }

    render() {
        const quests = this.props.quests ?? {};
        const completedQuests = asTopsRecordList(quests.completed_quests);
        const completedGuideQuests = asTopsRecordList(
            quests.completed_guide_quests,
        );

        return (
            <section className="space-y-4" aria-label="Completed quests">
                <BasicCard>
                    <h2 className="text-xl font-semibold">Quest Summary</h2>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Completed normal quests and guide quests from public
                        completion records.
                    </p>
                    <dl className="mt-4 grid gap-3 sm:grid-cols-2">
                        <div>
                            <dt className="text-sm font-semibold">
                                Completed Quests
                            </dt>
                            <dd className="text-2xl font-bold tabular-nums">
                                {formatTopsValue(quests.completed_quest_count)}
                            </dd>
                        </div>
                        <div>
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
                <TopsChartCard
                    title="Quest Completion"
                    description="Completed normal quests and guide quests from real completion timestamps."
                    chart={quests.completion_chart as any}
                    xAxisLabel="Completion Time"
                    yAxisLabel="Completions"
                    timeSeries={true}
                />
                <BasicCard>
                    <h2 className="text-xl font-semibold">
                        Completed Quest Details
                    </h2>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Search, filter, and inspect completed public quest
                        details.
                    </p>
                    <div className="mt-4">
                        <Tabs tabs={QUEST_TABS} full_width={true}>
                            <TabPanel key="quests">
                                {this.renderQuestsTab(completedQuests)}
                            </TabPanel>
                            <TabPanel key="guide-quests">
                                {this.renderGuideQuestsTab(
                                    completedGuideQuests,
                                )}
                            </TabPanel>
                        </Tabs>
                    </div>
                </BasicCard>
                {this.state.selectedQuest !== null ? (
                    <QuestDetailModal
                        quest={this.state.selectedQuest}
                        onClose={() => this.setState({ selectedQuest: null })}
                    />
                ) : null}
            </section>
        );
    }
}
