import React from "react";
import Select, { SingleValue } from "react-select";
import BasicCard from "../../../../../game/components/ui/cards/basic-card";
import TopsEmptyState from "../../shared/components/tops-empty-state";
import ProfileSectionProps from "../types/profile-section-props";
import TopsValue from "../../shared/types/tops-value";
import { asTopsRecordList } from "../../shared/helpers/tops-value-helpers";
import TopsChartCard from "./profile/sheet-inspect/tops-chart-card";
import { formatTopsValue } from "../../shared/helpers/tops-format-value";

const perPage = 10;

type Option = {
    label: string;
    value: string;
};

export default class ProfileKingdoms extends React.Component<
    ProfileSectionProps,
    {
        search: string;
        selectedPlane: Option | null;
        page: number;
    }
> {
    state = {
        search: "",
        selectedPlane: null,
        page: 1,
    };

    kingdoms(): Record<string, TopsValue> {
        return this.props.kingdoms ?? {};
    }

    planeOptions(rows: Record<string, TopsValue>[]): Option[] {
        return rows
            .map((kingdom: Record<string, TopsValue>) =>
                String(kingdom.map ?? ""),
            )
            .filter((plane: string) => plane !== "")
            .filter(
                (plane: string, index: number, planes: string[]) =>
                    planes.indexOf(plane) === index,
            )
            .sort()
            .map((plane: string) => ({ label: plane, value: plane }));
    }

    filteredRows(rows: Record<string, TopsValue>[]) {
        const search = this.state.search.toLowerCase();

        return rows.filter((kingdom: Record<string, TopsValue>) => {
            const matchesSearch = String(kingdom.name ?? "")
                .toLowerCase()
                .includes(search);
            const matchesPlane =
                this.state.selectedPlane === null ||
                kingdom.map === this.state.selectedPlane.value;

            return matchesSearch && matchesPlane;
        });
    }

    paginatedRows(rows: Record<string, TopsValue>[]) {
        const start = (this.state.page - 1) * perPage;

        return rows.slice(start, start + perPage);
    }

    totalPages(rows: Record<string, TopsValue>[]) {
        return Math.max(1, Math.ceil(rows.length / perPage));
    }

    renderKingdom(kingdom: Record<string, TopsValue>) {
        return (
            <article
                key={String(kingdom.id)}
                className="rounded-sm border border-gray-200 p-4 dark:border-gray-700"
            >
                <h3 className="font-semibold">
                    {kingdom.name ?? "Unknown Kingdom"}
                </h3>
                <dl className="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt className="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Plane
                        </dt>
                        <dd>{formatTopsValue(kingdom.map)}</dd>
                    </div>
                    <div>
                        <dt className="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Capital
                        </dt>
                        <dd>{kingdom.is_capital ? "Yes" : "No"}</dd>
                    </div>
                    <div>
                        <dt className="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Treasury
                        </dt>
                        <dd>{formatTopsValue(kingdom.treasury)}</dd>
                    </div>
                    <div>
                        <dt className="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Gold Bars
                        </dt>
                        <dd>{formatTopsValue(kingdom.gold_bars)}</dd>
                    </div>
                    <div>
                        <dt className="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Population
                        </dt>
                        <dd>{formatTopsValue(kingdom.current_population)}</dd>
                    </div>
                    <div>
                        <dt className="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Morale
                        </dt>
                        <dd>{formatTopsValue(kingdom.current_morale)}%</dd>
                    </div>
                </dl>
            </article>
        );
    }

    renderPagination(rows: Record<string, TopsValue>[]) {
        const totalPages = this.totalPages(rows);

        return (
            <div className="mt-4 flex flex-col gap-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                <span className="text-gray-600 dark:text-gray-400">
                    Page {this.state.page} of {totalPages}
                </span>
                <div className="flex gap-2">
                    <button
                        type="button"
                        className="rounded-sm border border-gray-300 px-3 py-1 font-semibold disabled:opacity-50 dark:border-gray-600"
                        disabled={this.state.page === 1}
                        onClick={() =>
                            this.setState({
                                page: Math.max(1, this.state.page - 1),
                            })
                        }
                    >
                        Previous
                    </button>
                    <button
                        type="button"
                        className="rounded-sm border border-gray-300 px-3 py-1 font-semibold disabled:opacity-50 dark:border-gray-600"
                        disabled={this.state.page === totalPages}
                        onClick={() =>
                            this.setState({
                                page: Math.min(totalPages, this.state.page + 1),
                            })
                        }
                    >
                        Next
                    </button>
                </div>
            </div>
        );
    }

    render() {
        const kingdomRows = asTopsRecordList(this.kingdoms().kingdoms);
        const filteredRows = this.filteredRows(kingdomRows);
        const visibleRows = this.paginatedRows(filteredRows);
        const planeOptions = this.planeOptions(kingdomRows);

        return (
            <section className="space-y-4" aria-label="Kingdoms">
                <div className="grid gap-4 lg:grid-cols-2">
                    <TopsChartCard
                        title="Kingdom Summary"
                        description="Public kingdom totals from this character profile."
                        chart={this.kingdoms().kingdom_summary_chart as any}
                        xAxisLabel="Metric"
                        yAxisLabel="Value"
                    />
                    <TopsChartCard
                        title="Resource Totals"
                        description="Current public resource totals across player-owned kingdoms."
                        chart={this.kingdoms().resource_totals_chart as any}
                        xAxisLabel="Resource"
                        yAxisLabel="Amount"
                    />
                </div>
                <TopsChartCard
                    title="Top Kingdoms"
                    description="Treasury, gold bars, population, and morale for each public kingdom row."
                    chart={this.kingdoms().top_kingdoms_chart as any}
                    xAxisLabel="Metric"
                    yAxisLabel="Value"
                />
                <BasicCard>
                    <h2 className="text-xl font-semibold">Top Kingdoms</h2>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Search and filter public kingdom rows.
                    </p>
                    <div className="mt-4 grid gap-3 md:grid-cols-2">
                        <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Search Kingdoms
                            <input
                                type="search"
                                className="mt-1 w-full rounded-sm border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                value={this.state.search}
                                onChange={(event) =>
                                    this.setState({
                                        search: event.target.value,
                                        page: 1,
                                    })
                                }
                            />
                        </label>
                        <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Plane
                            <Select
                                className="mt-1 text-sm"
                                classNamePrefix="react-select"
                                options={planeOptions}
                                isClearable={true}
                                value={this.state.selectedPlane}
                                onChange={(option: SingleValue<Option>) =>
                                    this.setState({
                                        selectedPlane: option,
                                        page: 1,
                                    })
                                }
                                aria-label="Filter kingdoms by plane"
                                menuPortalTarget={document.body}
                            />
                        </label>
                    </div>
                    <div className="mt-4 grid gap-3">
                        {visibleRows.length === 0 ? (
                            <TopsEmptyState message="No player-owned kingdoms match these filters." />
                        ) : (
                            visibleRows.map(
                                (kingdom: Record<string, TopsValue>) =>
                                    this.renderKingdom(kingdom),
                            )
                        )}
                    </div>
                    {this.renderPagination(filteredRows)}
                </BasicCard>
            </section>
        );
    }
}
