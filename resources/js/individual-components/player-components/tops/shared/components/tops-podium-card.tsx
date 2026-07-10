import React from "react";
import TopsDisplayField from "../types/tops-display-field";
import { formatTopsCompactFieldValue } from "../helpers/tops-format-value";
import {
    podiumAccentClasses,
    podiumCardClasses,
    podiumPillClasses,
} from "../helpers/tops-rank-styles";
import TopsPodiumCardProps from "../types/tops-podium-card-props";
import TopsStatList from "./tops-stat-list";
import TopsStatListItem from "../types/tops-stat-list-item";

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
            return "block w-full whitespace-nowrap text-center text-3xl font-black leading-none tracking-tight tabular-nums";
        }

        return "block w-full whitespace-nowrap text-center text-2xl font-black leading-none tracking-tight tabular-nums";
    }

    supportingMetricItems(): TopsStatListItem[] {
        return this.props.supportingMetrics
            .slice(0, 4)
            .map((field: TopsDisplayField) => ({
                label: field.label,
                value: this.props.row[field.key],
                type: field.type,
            }));
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
                <div className="flex min-h-[36px] flex-col items-center justify-start gap-2">
                    <span
                        className={
                            "rounded-full border px-3 py-0.5 text-xs font-bold uppercase tracking-wide " +
                            podiumPillClasses(this.props.rank)
                        }
                    >
                        {this.props.placement}
                    </span>
                    <p className="text-sm font-bold">Rank {this.props.rank}</p>
                </div>

                <div className="flex flex-1 flex-col items-center justify-center gap-4 py-2 text-center">
                    <h3 className="max-w-full break-words text-center text-lg font-bold leading-tight">
                        {this.props.row.character_name ?? "Unknown Character"}
                    </h3>
                    <div className="grid w-full gap-4 text-center">
                        <p className={this.primaryMetricLabelClasses()}>
                            {this.props.primaryMetric.label}
                        </p>
                        <p className={this.primaryValueClasses()}>
                            {formatTopsCompactFieldValue(
                                this.props.primaryMetric,
                                primaryValue,
                            )}
                        </p>
                    </div>
                </div>

                <TopsStatList
                    items={this.supportingMetricItems()}
                    compact={true}
                    accentClassName={podiumAccentClasses(this.props.rank)}
                />
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
