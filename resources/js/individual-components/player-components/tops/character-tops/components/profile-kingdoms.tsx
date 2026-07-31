import React from "react";
import Select, { SingleValue } from "react-select";
import BasicCard from "../../../../../game/components/ui/cards/basic-card";
import TopsEmptyState from "../../shared/components/tops-empty-state";
import { KingdomProfile } from "../types/character-profile";
import TopsValue from "../../shared/types/tops-value";
import { formatTopsValue } from "../../shared/helpers/tops-format-value";
import topsSelectStyles from "../../shared/styles/tops-select-styles";

const perPage = 10;

type Option = {
    label: string;
    value: string;
};

export default class ProfileKingdoms extends React.Component<
    { kingdoms?: KingdomProfile },
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

    tableHeaders(): {
        key: string;
        label: string;
        align: "left" | "right" | "center";
    }[] {
        return [
            { key: "name", label: "Kingdom Name", align: "left" },
            { key: "map", label: "Plane", align: "left" },
            { key: "is_capital", label: "Capital", align: "center" },
            { key: "treasury", label: "Treasury", align: "right" },
            { key: "gold_bars", label: "Gold Bars", align: "right" },
            { key: "current_population", label: "Population", align: "right" },
            { key: "current_morale", label: "Morale", align: "right" },
            { key: "current_stone", label: "Stone", align: "right" },
            { key: "current_wood", label: "Wood", align: "right" },
            { key: "current_clay", label: "Clay", align: "right" },
            { key: "current_iron", label: "Iron", align: "right" },
            { key: "current_steel", label: "Steel", align: "right" },
        ];
    }

    alignmentClasses(alignment: "left" | "right" | "center"): string {
        if (alignment === "right") {
            return "text-right";
        }

        if (alignment === "center") {
            return "text-center";
        }

        return "text-left";
    }

    renderKingdomRow(kingdom: Record<string, TopsValue>) {
        return (
            <tr key={String(kingdom.id)}>
                <td className="whitespace-nowrap px-4 py-3 text-left font-semibold text-gray-900 dark:text-gray-100">
                    {formatTopsValue(kingdom.name)}
                </td>
                <td className="px-4 py-3 text-left text-gray-900 dark:text-gray-100">
                    {formatTopsValue(kingdom.map)}
                </td>
                <td className="px-4 py-3 text-center text-gray-900 dark:text-gray-100">
                    {kingdom.is_capital ? "Yes" : "No"}
                </td>
                <td className="whitespace-nowrap px-4 py-3 text-right tabular-nums text-gray-900 dark:text-gray-100">
                    {formatTopsValue(kingdom.treasury)}
                </td>
                <td className="whitespace-nowrap px-4 py-3 text-right tabular-nums text-gray-900 dark:text-gray-100">
                    {formatTopsValue(kingdom.gold_bars)}
                </td>
                <td className="whitespace-nowrap px-4 py-3 text-right tabular-nums text-gray-900 dark:text-gray-100">
                    {formatTopsValue(kingdom.current_population)}
                </td>
                <td className="whitespace-nowrap px-4 py-3 text-right tabular-nums text-gray-900 dark:text-gray-100">
                    {formatTopsValue(kingdom.current_morale)}%
                </td>
                <td className="whitespace-nowrap px-4 py-3 text-right tabular-nums text-gray-900 dark:text-gray-100">
                    {formatTopsValue(kingdom.current_stone)}
                </td>
                <td className="whitespace-nowrap px-4 py-3 text-right tabular-nums text-gray-900 dark:text-gray-100">
                    {formatTopsValue(kingdom.current_wood)}
                </td>
                <td className="whitespace-nowrap px-4 py-3 text-right tabular-nums text-gray-900 dark:text-gray-100">
                    {formatTopsValue(kingdom.current_clay)}
                </td>
                <td className="whitespace-nowrap px-4 py-3 text-right tabular-nums text-gray-900 dark:text-gray-100">
                    {formatTopsValue(kingdom.current_iron)}
                </td>
                <td className="whitespace-nowrap px-4 py-3 text-right tabular-nums text-gray-900 dark:text-gray-100">
                    {formatTopsValue(kingdom.current_steel)}
                </td>
            </tr>
        );
    }

    renderKingdomTable(rows: Record<string, TopsValue>[]) {
        const headers = this.tableHeaders();

        return (
            <div className="hidden overflow-x-auto rounded-sm border border-gray-200 dark:border-gray-700 md:block">
                <table className="w-full table-auto border-collapse text-sm">
                    <thead className="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            {headers.map((header) => (
                                <th
                                    key={header.key}
                                    scope="col"
                                    className={
                                        this.alignmentClasses(header.align) +
                                        " px-4 py-3 text-xs font-bold uppercase tracking-wide text-gray-700 dark:text-gray-300"
                                    }
                                >
                                    {header.label}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                        {rows.map((kingdom: Record<string, TopsValue>) =>
                            this.renderKingdomRow(kingdom),
                        )}
                    </tbody>
                </table>
            </div>
        );
    }

    renderKingdomCards(rows: Record<string, TopsValue>[]) {
        return (
            <div className="grid gap-3 md:hidden">
                {rows.map((kingdom: Record<string, TopsValue>) => (
                    <article
                        key={String(kingdom.id)}
                        className="rounded-sm border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <h3 className="font-semibold text-gray-900 dark:text-gray-100">
                            {formatTopsValue(kingdom.name)}
                        </h3>
                        <dl className="mt-3 grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                            {this.tableHeaders()
                                .slice(1)
                                .flatMap((header) => [
                                    <dt
                                        key={`${header.key}-label`}
                                        className="font-semibold text-gray-600 dark:text-gray-400"
                                    >
                                        {header.label}
                                    </dt>,
                                    <dd
                                        key={`${header.key}-value`}
                                        className="text-right text-gray-900 dark:text-gray-100"
                                    >
                                        {header.key === "is_capital"
                                            ? kingdom.is_capital
                                                ? "Yes"
                                                : "No"
                                            : `${formatTopsValue(kingdom[header.key])}${header.key === "current_morale" ? "%" : ""}`}
                                    </dd>,
                                ])}
                        </dl>
                    </article>
                ))}
            </div>
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
                        aria-label="Previous kingdom results page"
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
                        aria-label="Next kingdom results page"
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
        const kingdomRows = this.props.kingdoms?.kingdoms ?? [];
        const filteredRows = this.filteredRows(kingdomRows);
        const visibleRows = this.paginatedRows(filteredRows);
        const planeOptions = this.planeOptions(kingdomRows);

        return (
            <section className="space-y-4" aria-label="Kingdoms">
                <BasicCard>
                    <h2 className="text-xl font-semibold">Kingdoms</h2>
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
                                styles={topsSelectStyles}
                                menuPortalTarget={document.body}
                            />
                        </label>
                    </div>
                    <div className="mt-4">
                        {visibleRows.length === 0 ? (
                            <TopsEmptyState message="No player-owned kingdoms match these filters." />
                        ) : (
                            <>
                                {this.renderKingdomCards(visibleRows)}
                                {this.renderKingdomTable(visibleRows)}
                            </>
                        )}
                    </div>
                    {this.renderPagination(filteredRows)}
                </BasicCard>
            </section>
        );
    }
}
