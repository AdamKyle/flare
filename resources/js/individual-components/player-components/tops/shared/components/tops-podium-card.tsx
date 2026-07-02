import React from "react";
import TopsDisplayField from "../types/tops-display-field";
import { formatTopsCompactValue } from "../helpers/tops-format-value";
import {
    podiumAccentClasses,
    podiumCardClasses,
    podiumPillClasses,
} from "../helpers/tops-rank-styles";
import TopsPodiumCardProps from "../types/tops-podium-card-props";

export default class TopsPodiumCard extends React.Component<TopsPodiumCardProps> {
    cardClasses(isClickable: boolean): string {
        const baseClasses =
            podiumCardClasses(this.props.rank, this.props.isFeatured) +
            " " +
            this.props.cardWidthClass +
            " transition duration-200 ease-out";

        if (!isClickable) {
            return baseClasses;
        }

        return (
            baseClasses +
            " hover:[transform:translateY(-0.125rem)_scale(1.005)] hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-regent-st-blue-400 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900"
        );
    }

    primaryValueClasses(): string {
        if (this.props.rank === 1) {
            return "block w-full whitespace-nowrap text-center text-5xl font-black leading-none tracking-tight tabular-nums";
        }

        return "block w-full whitespace-nowrap text-center text-4xl font-black leading-none tracking-tight tabular-nums";
    }

    metricCellValueClasses(): string {
        return "block w-full whitespace-nowrap text-center font-black tabular-nums";
    }

    metricLabelClasses(): string {
        return (
            "block w-full text-center text-xs font-bold " +
            podiumAccentClasses(this.props.rank)
        );
    }

    primaryMetricLabelClasses(): string {
        return (
            "block w-full text-center text-sm font-bold " +
            podiumAccentClasses(this.props.rank)
        );
    }

    renderInnerContent() {
        const primaryValue = this.props.row[this.props.primaryMetric.key];

        return (
            <React.Fragment>
                <div className="flex min-h-[64px] flex-col items-center justify-start gap-2">
                    <span
                        className={
                            "rounded-full border px-4 py-1 text-xs font-bold uppercase tracking-wide " +
                            podiumPillClasses(this.props.rank)
                        }
                    >
                        {this.props.placement}
                    </span>
                    <p className="text-lg font-bold">Rank {this.props.rank}</p>
                </div>

                <div className="flex flex-1 flex-col items-center justify-center gap-4 py-6 text-center">
                    <h3 className="max-w-full break-words text-center text-2xl font-bold leading-tight">
                        {this.props.row.character_name ?? "Unknown Character"}
                    </h3>
                    <div className="w-full text-center">
                        <p className={this.primaryMetricLabelClasses()}>
                            {this.props.primaryMetric.label}
                        </p>
                        <p className={this.primaryValueClasses()}>
                            {formatTopsCompactValue(primaryValue)}
                        </p>
                    </div>
                </div>

                <div className="grid w-full grid-cols-2 gap-3 text-sm">
                    {this.props.supportingMetrics
                        .slice(0, 4)
                        .map((field: TopsDisplayField) => (
                            <div
                                key={field.key}
                                className="flex min-h-[72px] flex-col items-center justify-center gap-1 rounded-sm bg-white/40 px-3 py-2 text-center dark:bg-black/15"
                            >
                                <p className={this.metricLabelClasses()}>
                                    {field.label}
                                </p>
                                <p className={this.metricCellValueClasses()}>
                                    {formatTopsCompactValue(
                                        this.props.row[field.key],
                                    )}
                                </p>
                            </div>
                        ))}
                </div>
            </React.Fragment>
        );
    }

    render() {
        const profileUrl = this.props.row.character_profile_url;

        if (profileUrl !== null && profileUrl !== "") {
            return (
                <a
                    href={profileUrl}
                    className={this.cardClasses(true)}
                    aria-label={
                        "View Rank " +
                        this.props.rank +
                        " character " +
                        (this.props.row.character_name ?? "Unknown Character") +
                        " profile"
                    }
                >
                    {this.renderInnerContent()}
                </a>
            );
        }

        return (
            <article
                className={this.cardClasses(false)}
                aria-label={
                    "Rank " +
                    this.props.rank +
                    " " +
                    this.props.placement +
                    " placement for " +
                    (this.props.row.character_name ?? "Unknown Character")
                }
            >
                {this.renderInnerContent()}
            </article>
        );
    }
}
