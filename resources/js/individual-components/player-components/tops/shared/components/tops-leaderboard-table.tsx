import React from "react";
import Select, { SingleValue } from "react-select";
import TopsDisplayField from "../types/tops-display-field";
import TopsLeaderboardRow from "../types/tops-leaderboard-row";
import TopsCharacterNameLink from "./tops-character-name-link";
import TopsRankBadge from "./tops-rank-badge";
import { formatTopsValue } from "../helpers/tops-format-value";
import {
    tableCellTextClasses,
    tableRowClasses,
} from "../helpers/tops-rank-styles";
import TopsLeaderboardTableProps from "../types/tops-leaderboard-table-props";
import TopsLeaderboardTableState from "../types/tops-leaderboard-table-state";
import TopsValue from "../types/tops-value";

export default class TopsLeaderboardTable extends React.Component<
    TopsLeaderboardTableProps,
    TopsLeaderboardTableState
> {
    constructor(props: TopsLeaderboardTableProps) {
        super(props);

        this.state = {
            sortKey: "rank",
            sortDirection: "asc",
            currentPage: 1,
            perPage: 10,
        };
    }

    isNumericField(field: TopsDisplayField): boolean {
        return field.type === "number";
    }

    isBooleanField(field: TopsDisplayField): boolean {
        return field.type === "boolean";
    }

    fieldAlignment(field: TopsDisplayField): "left" | "right" | "center" {
        if (field.align) {
            return field.align;
        }

        if (this.isNumericField(field)) {
            return "right";
        }

        if (this.isBooleanField(field)) {
            return "center";
        }

        return "left";
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

    sortableValue(row: TopsLeaderboardRow): TopsValue {
        return row[this.state.sortKey];
    }

    sortedRows(): TopsLeaderboardRow[] {
        return [...this.props.rows].sort(
            (left: TopsLeaderboardRow, right: TopsLeaderboardRow) => {
                const leftValue = this.sortableValue(left);
                const rightValue = this.sortableValue(right);

                if (leftValue === rightValue) {
                    return 0;
                }

                if (leftValue === null || typeof leftValue === "undefined") {
                    return 1;
                }

                if (rightValue === null || typeof rightValue === "undefined") {
                    return -1;
                }

                if (
                    typeof leftValue === "number" &&
                    typeof rightValue === "number"
                ) {
                    return this.state.sortDirection === "asc"
                        ? leftValue - rightValue
                        : rightValue - leftValue;
                }

                const comparison = String(leftValue).localeCompare(
                    String(rightValue),
                );

                return this.state.sortDirection === "asc"
                    ? comparison
                    : comparison * -1;
            },
        );
    }

    paginatedRows(): TopsLeaderboardRow[] {
        const startIndex = (this.state.currentPage - 1) * this.state.perPage;

        return this.sortedRows().slice(
            startIndex,
            startIndex + this.state.perPage,
        );
    }

    totalPages(): number {
        return Math.max(
            1,
            Math.ceil(this.props.rows.length / this.state.perPage),
        );
    }

    sortBy(sortKey: string): void {
        this.setState((state: TopsLeaderboardTableState) => ({
            sortKey,
            sortDirection:
                state.sortKey === sortKey && state.sortDirection === "asc"
                    ? "desc"
                    : "asc",
            currentPage: 1,
        }));
    }

    changePerPage(value: string): void {
        this.setState({
            perPage: Number(value),
            currentPage: 1,
        });
    }

    previousPage(): void {
        this.setState({
            currentPage: Math.max(1, this.state.currentPage - 1),
        });
    }

    nextPage(): void {
        this.setState({
            currentPage: Math.min(
                this.totalPages(),
                this.state.currentPage + 1,
            ),
        });
    }

    renderSortLabel(sortKey: string): string {
        if (this.state.sortKey !== sortKey) {
            return "";
        }

        return this.state.sortDirection === "asc"
            ? " ascending"
            : " descending";
    }

    renderHeader(
        label: string,
        sortKey: string,
        alignment: "left" | "right" | "center" = "left",
    ) {
        const justifyClass =
            alignment === "right"
                ? "justify-end"
                : alignment === "center"
                  ? "justify-center"
                  : "justify-start";

        return (
            <th
                scope="col"
                className={
                    this.alignmentClasses(alignment) +
                    " px-4 py-3 text-xs font-bold uppercase tracking-wide text-gray-700 dark:text-gray-300"
                }
                aria-sort={
                    this.state.sortKey === sortKey
                        ? this.state.sortDirection === "asc"
                            ? "ascending"
                            : "descending"
                        : "none"
                }
            >
                <button
                    type="button"
                    className={
                        justifyClass +
                        " inline-flex w-full items-center gap-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-regent-st-blue-400 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900"
                    }
                    onClick={() => this.sortBy(sortKey)}
                >
                    {label}
                    <span className="sr-only">
                        {this.renderSortLabel(sortKey)}
                    </span>
                </button>
            </th>
        );
    }

    renderPagination() {
        const options = [
            { label: "10", value: "10" },
            { label: "25", value: "25" },
            { label: "50", value: "50" },
        ];
        const selectedOption =
            options.find(
                (option) => option.value === String(this.state.perPage),
            ) ?? options[0];

        return (
            <div className="flex flex-col gap-3 border-t border-gray-200 px-4 py-3 text-sm dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
                <label className="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                    <span>Rows</span>
                    <div className="w-24">
                        <Select
                            className="text-sm"
                            classNamePrefix="react-select"
                            options={options}
                            value={selectedOption}
                            onChange={(
                                option: SingleValue<{
                                    label: string;
                                    value: string;
                                }>,
                            ) => this.changePerPage(option?.value ?? "10")}
                            aria-label="Select rows per page"
                            menuPortalTarget={document.body}
                        />
                    </div>
                </label>
                <div className="flex items-center gap-3">
                    <button
                        type="button"
                        className="rounded-sm border border-gray-300 px-3 py-1 font-semibold text-gray-700 disabled:opacity-50 dark:border-gray-600 dark:text-gray-300"
                        disabled={this.state.currentPage === 1}
                        onClick={() => this.previousPage()}
                    >
                        Previous
                    </button>
                    <span className="text-gray-700 dark:text-gray-300">
                        Page {this.state.currentPage} of {this.totalPages()}
                    </span>
                    <button
                        type="button"
                        className="rounded-sm border border-gray-300 px-3 py-1 font-semibold text-gray-700 disabled:opacity-50 dark:border-gray-600 dark:text-gray-300"
                        disabled={this.state.currentPage === this.totalPages()}
                        onClick={() => this.nextPage()}
                    >
                        Next
                    </button>
                </div>
            </div>
        );
    }

    renderField(row: TopsLeaderboardRow, field: TopsDisplayField) {
        const isNumericField = this.isNumericField(field);
        const alignment = this.fieldAlignment(field);

        return (
            <td
                key={field.key}
                className={
                    this.alignmentClasses(alignment) +
                    " px-4 py-4 align-middle font-semibold " +
                    tableCellTextClasses(row.rank) +
                    " " +
                    (isNumericField ? "whitespace-nowrap tabular-nums" : "")
                }
            >
                {formatTopsValue(row[field.key])}
            </td>
        );
    }

    renderRow(row: TopsLeaderboardRow) {
        return (
            <tr
                key={row.rank + "-" + row.character_id}
                className={tableRowClasses(row.rank)}
            >
                <td
                    className={
                        "w-32 whitespace-nowrap px-4 py-4 align-middle text-center " +
                        tableCellTextClasses(row.rank)
                    }
                >
                    <TopsRankBadge rank={row.rank} />
                </td>
                <td
                    className={
                        "px-4 py-4 align-middle text-left " +
                        tableCellTextClasses(row.rank)
                    }
                >
                    <TopsCharacterNameLink
                        characterId={row.character_id}
                        characterName={row.character_name}
                        variant="table"
                        rank={row.rank}
                    />
                </td>
                {this.props.fields.map((field: TopsDisplayField) =>
                    this.renderField(row, field),
                )}
            </tr>
        );
    }

    render() {
        const minTableWidth = this.props.minTableWidth ?? "min-w-[900px]";

        return (
            <div className="hidden overflow-hidden rounded-sm border border-gray-200 dark:border-gray-700 lg:block">
                <div className="overflow-x-auto">
                    <table
                        className={
                            minTableWidth +
                            " w-full table-auto border-collapse text-sm"
                        }
                    >
                        <thead className="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                {this.renderHeader("Rank", "rank", "center")}
                                {this.renderHeader(
                                    "Character",
                                    "character_name",
                                )}
                                {this.props.fields.map(
                                    (field: TopsDisplayField) => (
                                        <React.Fragment key={field.key}>
                                            {this.renderHeader(
                                                field.label,
                                                field.key,
                                                this.fieldAlignment(field),
                                            )}
                                        </React.Fragment>
                                    ),
                                )}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                            {this.paginatedRows().map(
                                (row: TopsLeaderboardRow) =>
                                    this.renderRow(row),
                            )}
                        </tbody>
                    </table>
                </div>
                {this.renderPagination()}
            </div>
        );
    }
}
