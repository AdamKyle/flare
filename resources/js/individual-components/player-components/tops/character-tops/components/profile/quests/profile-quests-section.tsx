import React from "react";
import Select, { SingleValue } from "react-select";
import topsSelectStyles from "../../../../shared/styles/tops-select-styles";
import BasicCard from "../../../../../../../game/components/ui/cards/basic-card";
import Tabs from "../../../../../../../game/components/ui/tabs/tabs";
import TabPanel from "../../../../../../../game/components/ui/tabs/tab-panel";
import TopsEmptyState from "../../../../shared/components/tops-empty-state";
import ProfileQuestsSectionProps from "../../../types/profile/quests/profile-quests-section-props";
import TopsChartCard from "../sheet-inspect/tops-chart-card";
import { formatLocalDateTime } from "../../../../../../../game/lib/game/format-local-date";
import QuestDetailsModal from "../../../../../../../game/sections/components/quests/modals/quest-details-modal";
import GuideQuest from "../../../../../guide-quests/modals/guide-quest";
import { PublicQuestRow } from "../../../types/character-profile";

const QUEST_TABS = [
    { key: "quests", name: "Quests" },
    { key: "guide-quests", name: "Guide Quests" },
];
const perPage = 10;

type Option = {
    label: string;
    value: string;
};

export default class ProfileQuestsSection extends React.Component<
    ProfileQuestsSectionProps,
    {
        selectedNormalQuest: PublicQuestRow | null;
        selectedGuideQuest: PublicQuestRow | null;
        questSearch: string;
        guideQuestSearch: string;
        selectedPlane: Option | null;
        questPage: number;
        guideQuestPage: number;
    }
> {
    state = {
        selectedNormalQuest: null,
        selectedGuideQuest: null,
        questSearch: "",
        guideQuestSearch: "",
        selectedPlane: null,
        questPage: 1,
        guideQuestPage: 1,
    };

    planeOptions(quests: PublicQuestRow[]): Option[] {
        return quests
            .map((quest) => String(quest.plane ?? ""))
            .filter((plane: string) => plane !== "")
            .filter(
                (plane: string, index: number, planes: string[]) =>
                    planes.indexOf(plane) === index,
            )
            .sort()
            .map((plane: string) => ({ label: plane, value: plane }));
    }

    filteredQuests(quests: PublicQuestRow[]) {
        const search = this.state.questSearch.toLowerCase();

        return quests.filter((quest) => {
            const matchesSearch = String(quest.name ?? "")
                .toLowerCase()
                .includes(search);
            const matchesPlane =
                this.state.selectedPlane === null ||
                quest.plane === this.state.selectedPlane.value;

            return matchesSearch && matchesPlane;
        });
    }

    filteredGuideQuests(quests: PublicQuestRow[]) {
        const search = this.state.guideQuestSearch.toLowerCase();

        return quests.filter((quest) =>
            String(quest.name ?? "")
                .toLowerCase()
                .includes(search),
        );
    }

    paginatedRows(rows: PublicQuestRow[], page: number) {
        const start = (page - 1) * perPage;

        return rows.slice(start, start + perPage);
    }

    totalPages(rows: PublicQuestRow[]) {
        return Math.max(1, Math.ceil(rows.length / perPage));
    }

    renderPagination(
        page: number,
        rows: PublicQuestRow[],
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

    renderQuestRows(rows: PublicQuestRow[], guideQuest = false) {
        if (rows.length === 0) {
            return (
                <TopsEmptyState message="No public quest completions match these filters." />
            );
        }

        return (
            <ul className="grid gap-2">
                {rows.map((quest, index: number) => (
                    <li
                        key={String(quest.id ?? quest.name) + index}
                        className="rounded-sm border border-gray-200 p-3 dark:border-gray-700"
                    >
                        <button
                            type="button"
                            className="text-left font-semibold text-gray-900 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-regent-st-blue-400 dark:text-gray-100"
                            onClick={() =>
                                this.setState(
                                    guideQuest
                                        ? { selectedGuideQuest: quest }
                                        : { selectedNormalQuest: quest },
                                )
                            }
                        >
                            {quest.name ?? "Unknown Quest"}
                        </button>
                        <dl className="mt-2 grid grid-cols-[auto_minmax(0,1fr)] gap-x-3 gap-y-2 text-sm">
                            <dt className="font-semibold text-gray-700 dark:text-gray-300">
                                Completed
                            </dt>
                            <dd>{formatLocalDateTime(quest.completed_at)}</dd>
                            {quest.plane ? (
                                <React.Fragment>
                                    <dt className="font-semibold text-gray-700 dark:text-gray-300">
                                        Plane
                                    </dt>
                                    <dd>{String(quest.plane)}</dd>
                                </React.Fragment>
                            ) : null}
                        </dl>
                    </li>
                ))}
            </ul>
        );
    }

    renderQuestsTab(quests: PublicQuestRow[]) {
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
                            styles={topsSelectStyles}
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

    renderGuideQuestsTab(quests: PublicQuestRow[]) {
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
                {this.renderQuestRows(visibleRows, true)}
                {this.renderPagination(
                    this.state.guideQuestPage,
                    filteredRows,
                    (page: number) => this.setState({ guideQuestPage: page }),
                )}
            </div>
        );
    }

    render() {
        const quests = this.props.quests;
        const completedQuests = quests?.completed_quests ?? [];
        const completedGuideQuests = quests?.completed_guide_quests ?? [];

        return (
            <section className="space-y-4" aria-label="Completed quests">
                <TopsChartCard
                    title="Quest Summary"
                    description="Inspected character cumulative quest completions compared with everyone else."
                    chart={quests?.summary_chart}
                    xAxisLabel="Date"
                    yAxisLabel="Completed Quests"
                    timeSeries={true}
                />
                <TopsChartCard
                    title="Quest Completion"
                    description="Completed normal quests and guide quests from real completion timestamps."
                    chart={quests?.completion_chart}
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
                {this.state.selectedNormalQuest !== null ? (
                    <QuestDetailsModal
                        is_open={true}
                        handle_close={() =>
                            this.setState({ selectedNormalQuest: null })
                        }
                        quest_id={this.state.selectedNormalQuest.id}
                        character_id={0}
                        read_only={true}
                        preloaded_quest_details={
                            this.state.selectedNormalQuest.details
                        }
                        is_quest_complete={
                            this.state.selectedNormalQuest.viewer_has_completed
                        }
                        is_parent_complete={
                            this.state.selectedNormalQuest
                                .viewer_parent_complete
                        }
                        is_required_quest_complete={
                            this.state.selectedNormalQuest
                                .viewer_required_quest_complete
                        }
                        is_required_quest_chain_complete={
                            this.state.selectedNormalQuest
                                .viewer_required_quest_chain_complete
                        }
                        required_quest_chain_details={
                            this.state.selectedNormalQuest
                                .required_quest_chain_details
                        }
                        completed_quests={
                            this.state.selectedNormalQuest
                                .viewer_completed_quest_ids ?? []
                        }
                    />
                ) : null}
                {this.state.selectedGuideQuest !== null ? (
                    <GuideQuest
                        is_open={true}
                        manage_modal={() =>
                            this.setState({ selectedGuideQuest: null })
                        }
                        user_id={0}
                        view_port={window.innerWidth}
                        read_only={true}
                        preloaded_guide_quest={this.state.selectedGuideQuest}
                        viewer_has_access={Boolean(
                            this.state.selectedGuideQuest
                                .viewer_has_completed ||
                                this.state.selectedGuideQuest
                                    .viewer_has_unlocked,
                        )}
                    />
                ) : null}
            </section>
        );
    }
}
