import React from "react";
import TopsDisplayField from "../types/tops-display-field";
import TopsLeaderboardRow from "../types/tops-leaderboard-row";
import TopsCharacterNameLink from "./tops-character-name-link";
import TopsRankBadge from "./tops-rank-badge";
import TopsStatList from "./tops-stat-list";
import TopsStatListItem from "../types/tops-stat-list-item";
import { formatTopsCompactFieldValue } from "../helpers/tops-format-value";
import {
    mobileCardClasses,
    podiumAccentClasses,
} from "../helpers/tops-rank-styles";
import TopsMobileLeaderboardCardsProps from "../types/tops-mobile-leaderboard-cards-props";

export default class TopsMobileLeaderboardCards extends React.Component<TopsMobileLeaderboardCardsProps> {
    cardClasses(rank: number): string {
        return mobileCardClasses(rank);
    }

    supportingMetricItems(row: TopsLeaderboardRow): TopsStatListItem[] {
        return this.props.supportingMetrics
            .slice(0, 4)
            .map((field: TopsDisplayField) => ({
                label: field.label,
                value: row[field.key],
                type: field.type,
            }));
    }

    renderCard(row: TopsLeaderboardRow) {
        return (
            <article
                key={row.rank}
                className={
                    "rounded-sm border-2 p-4 shadow-sm " +
                    this.cardClasses(row.rank)
                }
                aria-label={
                    "Rank " +
                    row.rank +
                    " " +
                    (row.character_name ?? "Unknown Character")
                }
            >
                <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                        <TopsRankBadge rank={row.rank} />
                        <h3 className="mt-3 break-words text-xl font-bold">
                            <TopsCharacterNameLink
                                characterId={row.character_id}
                                characterName={row.character_name}
                            />
                        </h3>
                    </div>
                </div>
                <div className="mt-4 text-center">
                    <p
                        className={
                            "block w-full text-center text-sm font-bold " +
                            podiumAccentClasses(row.rank)
                        }
                    >
                        {this.props.primaryMetric.label}
                    </p>
                    <p className="block w-full whitespace-nowrap text-center text-3xl font-black leading-none tabular-nums">
                        {formatTopsCompactFieldValue(
                            this.props.primaryMetric,
                            row[this.props.primaryMetric.key],
                        )}
                    </p>
                </div>
                <div className="mt-4">
                    <TopsStatList
                        items={this.supportingMetricItems(row)}
                        compact={true}
                        accentClassName={podiumAccentClasses(row.rank)}
                    />
                </div>
            </article>
        );
    }

    render() {
        return (
            <div className="grid gap-4 lg:hidden">
                {this.props.rows.map((row: TopsLeaderboardRow) =>
                    this.renderCard(row),
                )}
            </div>
        );
    }
}
