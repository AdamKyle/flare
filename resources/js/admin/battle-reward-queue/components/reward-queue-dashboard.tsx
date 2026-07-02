import React from "react";
import "../types/echo-window";
import {
    fetchCharacterRewardQueue,
    fetchRewardQueueCharacters,
    fetchRewardQueueCharts,
    fetchRewardQueueRequests,
    fetchRewardQueueStatusVolume,
    fetchRewardQueueSummary,
    fetchStaleRewardQueues,
    repairStaleRewardQueues,
} from "../ajax/reward-queue-api";
import {
    CharacterRow,
    ChartPoint,
    ChartsResponse,
    Paginated,
    RepairSummary,
    RequestFiltersType,
    Summary,
} from "../types/reward-queue";
import RewardQueueDashboardState from "../types/reward-queue-dashboard-state";
import CharacterQueueTable from "./character-queue-table";
import RequestHistory from "./request-history";
import StaleQueueAlert from "./stale-queue-alert";
import StaleQueueView from "./stale-queue-view";
import StatusVolumeChart from "./status-volume-chart";
import SummaryCards from "./summary-cards";

const emptySummary: Summary = {
    queued: 0,
    pending: 0,
    processing: 0,
    resumable: 0,
    completed: 0,
    failed: 0,
};

const emptyPage = <T,>(): Paginated<T> => ({
    data: [],
    current_page: 1,
    last_page: 1,
});

export default class RewardQueueDashboard extends React.Component<
    Record<string, never>,
    RewardQueueDashboardState
> {
    private debounceTimer?: ReturnType<typeof setTimeout>;

    private requestHistoryRef: React.RefObject<HTMLDivElement>;

    public constructor(props: Record<string, never>) {
        super(props);

        this.requestHistoryRef = React.createRef();

        this.state = {
            summary: emptySummary,
            charts: {
                last_hour: [],
                last_7_days: [],
                previous_7_days: [],
            },
            characters: emptyPage(),
            requests: emptyPage(),
            selectedCharacter: null,
            detailCharts: {},
            globalChart: [],
            range: "7",
            characterPage: 1,
            requestPage: 1,
            loading: true,
            error: "",
            message: "",
            showStaleView: false,
            filters: {
                status: "",
                priority: "",
                source_type: "",
                date_from: "",
                date_to: "",
                character_name: "",
                failed_reason: "",
                source_id: "",
            },
            staleQueues: [],
            repairing: false,
        };
    }

    componentDidMount() {
        void this.refresh();
        this.listenForUpdates();
    }

    componentWillUnmount() {
        clearTimeout(this.debounceTimer);
        window.Echo?.leave("admin-character-reward-queue");
    }

    listenForUpdates() {
        const channelName = "admin-character-reward-queue";
        const channel = window.Echo?.private(channelName);

        channel?.listen(".battle.reward.queue.updated", () => {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                void this.refresh();
            }, 500);
        });
    }

    async refreshStaleQueues() {
        const staleQueues = await fetchStaleRewardQueues();

        this.setState({
            staleQueues,
        });
    }

    async repair(): Promise<RepairSummary> {
        this.setState({
            repairing: true,
        });

        try {
            return await repairStaleRewardQueues();
        } finally {
            this.setState({
                repairing: false,
            });
        }
    }

    async refresh() {
        this.setState({
            error: "",
        });

        try {
            const [
                summaryData,
                chartsData,
                charactersData,
                globalData,
                requestData,
            ] = await Promise.all([
                fetchRewardQueueSummary(),
                fetchRewardQueueCharts(),
                fetchRewardQueueCharacters(this.state.characterPage),
                fetchRewardQueueStatusVolume(this.state.range),
                this.state.selectedCharacter
                    ? fetchCharacterRewardQueue(
                          this.state.selectedCharacter.character_id,
                          this.state.filters,
                          this.state.requestPage,
                      )
                    : fetchRewardQueueRequests(
                          this.state.filters,
                          this.state.requestPage,
                      ),
                this.refreshStaleQueues(),
            ]);

            this.setState({
                summary: summaryData,
                charts: chartsData,
                characters: charactersData,
                globalChart: globalData,
            });

            if ("requests" in requestData) {
                this.setState({
                    requests: requestData.requests,
                    detailCharts: requestData.charts,
                });
            } else {
                this.setState({
                    requests: requestData,
                    detailCharts: {},
                });
            }
        } catch {
            this.setState({
                error: "Reward queue data could not be loaded.",
            });
        } finally {
            this.setState({
                loading: false,
            });
        }
    }

    async repairQueues() {
        this.setState({
            error: "",
            message: "",
        });

        try {
            const result = await this.repair();

            this.setState({
                message: `Recovered ${result.repaired_queue_state_count} queue states. Resumed ${result.resumed_processing_request_count} ledger-backed requests, marked ${result.legacy_failed_processing_request_count} legacy requests failed, and restarted ${result.restarted_processor_count} processors.`,
            });

            await this.refresh();
        } catch {
            this.setState({
                error: "Stale reward queues could not be repaired.",
            });
        }
    }

    setFilters(filters: RequestFiltersType) {
        this.setState(
            {
                filters,
                requestPage: 1,
            },
            () => void this.refresh(),
        );
    }

    setStatusFilter(status: string) {
        this.setFilters({
            ...this.state.filters,
            status,
        });
    }

    selectCharacter(character: CharacterRow) {
        this.setState(
            {
                selectedCharacter: character,
                requestPage: 1,
            },
            () => void this.refresh(),
        );
    }

    clearCharacter() {
        this.setState(
            {
                selectedCharacter: null,
                requestPage: 1,
            },
            () => void this.refresh(),
        );
    }

    setCharacterPage(characterPage: number) {
        this.setState(
            {
                characterPage,
            },
            () => void this.refresh(),
        );
    }

    setRequestPage(requestPage: number) {
        this.setState(
            {
                requestPage,
            },
            () => void this.refresh(),
        );
    }

    setRange(range: string) {
        this.setState(
            {
                range,
            },
            () => void this.refresh(),
        );
    }

    setShowStaleView(showStaleView: boolean) {
        this.setState({
            showStaleView,
        });
    }

    render() {
        const {
            characters,
            charts,
            detailCharts,
            error,
            filters,
            globalChart,
            loading,
            message,
            range,
            repairing,
            requests,
            selectedCharacter,
            showStaleView,
            staleQueues,
            summary,
        } = this.state;

        return (
            <div className="space-y-5 pb-16 text-gray-900 dark:text-gray-100">
                {loading && (
                    <p role="status" aria-live="polite">
                        Loading reward queue data…
                    </p>
                )}
                {error && (
                    <p
                        className="rounded border border-red-400 bg-red-50 p-3 text-red-800 dark:bg-red-950 dark:text-red-100"
                        role="alert"
                    >
                        {error}
                    </p>
                )}
                {message && (
                    <p
                        className="rounded border border-green-400 bg-green-50 p-3 text-green-800 dark:bg-green-950 dark:text-green-100"
                        role="status"
                        aria-live="polite"
                    >
                        {message}
                    </p>
                )}

                {showStaleView ? (
                    <StaleQueueView
                        staleQueues={staleQueues}
                        repairing={repairing}
                        onBack={() => this.setShowStaleView(false)}
                        onRepair={() => void this.repairQueues()}
                    />
                ) : (
                    <>
                        {staleQueues.length > 0 && (
                            <StaleQueueAlert
                                count={staleQueues.length}
                                repairing={repairing}
                                onView={() => this.setShowStaleView(true)}
                                onRepair={() => void this.repairQueues()}
                            />
                        )}
                        <SummaryCards
                            summary={summary}
                            onFilter={(status: string) =>
                                this.setStatusFilter(status)
                            }
                        />
                        <div className="grid gap-4 xl:grid-cols-3">
                            <StatusVolumeChart
                                title="Last hour"
                                description="Request volume by status during the last 60 minutes."
                                points={charts.last_hour}
                            />
                            <StatusVolumeChart
                                title="Last 7 days"
                                description="Current seven-day reward request volume."
                                points={charts.last_7_days}
                            />
                            <StatusVolumeChart
                                title="Previous 7 days"
                                description="The previous completed seven-day period."
                                points={charts.previous_7_days}
                            />
                        </div>
                        <CharacterQueueTable
                            characters={characters}
                            onSelect={(character: CharacterRow) =>
                                this.selectCharacter(character)
                            }
                            onPageChange={(page: number) =>
                                this.setCharacterPage(page)
                            }
                        />
                        {selectedCharacter &&
                            ["1", "7", "14", "30"].map((days) => (
                                <StatusVolumeChart
                                    key={days}
                                    title={`${selectedCharacter.character_name}: ${days} day${days === "1" ? "" : "s"}`}
                                    description="Character-specific request volume by status."
                                    points={detailCharts[days] ?? []}
                                />
                            ))}
                        <div ref={this.requestHistoryRef}>
                            <RequestHistory
                                selectedCharacter={selectedCharacter}
                                requests={requests}
                                filters={filters}
                                onFiltersChange={(
                                    nextFilters: RequestFiltersType,
                                ) => this.setFilters(nextFilters)}
                                onClearCharacter={() => this.clearCharacter()}
                                onPageChange={(page: number) =>
                                    this.setRequestPage(page)
                                }
                            />
                        </div>
                        <div>
                            <label className="mb-2 block text-sm font-medium">
                                Global status range
                                <select
                                    className="ml-2 rounded border border-gray-300 bg-white p-2 dark:border-gray-600 dark:bg-gray-800"
                                    value={range}
                                    onChange={(event) =>
                                        this.setRange(event.target.value)
                                    }
                                >
                                    {[1, 7, 14, 30, 60, 120, 365].map(
                                        (days) => (
                                            <option value={days} key={days}>
                                                {days} day
                                                {days === 1 ? "" : "s"}
                                            </option>
                                        ),
                                    )}
                                </select>
                            </label>
                            <StatusVolumeChart
                                title="Global status volume"
                                description={`Completed, failed, pending, and processing requests during the selected ${range}-day range.`}
                                points={globalChart}
                            />
                        </div>
                    </>
                )}
            </div>
        );
    }
}
