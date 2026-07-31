import React from "react";
import TopsLeaderboardRow from "../types/tops-leaderboard-row";
import TopsPodiumCard from "./tops-podium-card";
import TopsPodiumProps from "../types/tops-podium-props";

export default class TopsPodium extends React.Component<TopsPodiumProps> {
    findRank(rank: number): TopsLeaderboardRow | undefined {
        return this.props.rows.find(
            (row: TopsLeaderboardRow) => row.rank === rank,
        );
    }

    cardWidthClass(cardsCount: number, isFeatured: boolean): string {
        if (cardsCount === 1) {
            return "w-full max-w-sm";
        }

        if (cardsCount === 3 && isFeatured) {
            return "w-full max-w-xs lg:max-w-sm";
        }

        return "w-full max-w-xs";
    }

    renderCard(
        row: TopsLeaderboardRow,
        placement: string,
        isFeatured: boolean,
        cardsCount: number,
    ) {
        return (
            <TopsPodiumCard
                key={row.rank}
                row={row}
                rank={row.rank}
                placement={placement}
                isFeatured={isFeatured}
                cardWidthClass={this.cardWidthClass(cardsCount, isFeatured)}
                primaryMetric={this.props.primaryMetric}
                supportingMetrics={this.props.supportingMetrics}
            />
        );
    }

    placement(rank: number): string {
        if (rank === 1) {
            return "Gold";
        }

        if (rank === 2) {
            return "Silver";
        }

        return "Bronze";
    }

    renderCards(rows: TopsLeaderboardRow[]) {
        const cardsCount = rows.length;

        return rows.map((row: TopsLeaderboardRow) =>
            this.renderCard(
                row,
                this.placement(row.rank),
                row.rank === 1,
                cardsCount,
            ),
        );
    }

    render() {
        const rankOne = this.findRank(1);
        const rankTwo = this.findRank(2);
        const rankThree = this.findRank(3);
        const mobileCards = [rankOne, rankTwo, rankThree].filter(
            (row): row is TopsLeaderboardRow => typeof row !== "undefined",
        );
        let desktopCards: TopsLeaderboardRow[] = [];

        if (rankOne && rankTwo && rankThree) {
            desktopCards = [rankThree, rankOne, rankTwo];
        } else if (rankOne && rankTwo) {
            desktopCards = [rankOne, rankTwo];
        } else if (rankOne) {
            desktopCards = [rankOne];
        }

        if (mobileCards.length === 0) {
            return null;
        }

        return (
            <section aria-label="Top three" className="mt-6 w-full">
                <div className="grid gap-4 lg:hidden">
                    {this.renderCards(mobileCards)}
                </div>
                <div className="hidden w-full items-end justify-center gap-6 lg:flex">
                    {this.renderCards(desktopCards)}
                </div>
            </section>
        );
    }
}
