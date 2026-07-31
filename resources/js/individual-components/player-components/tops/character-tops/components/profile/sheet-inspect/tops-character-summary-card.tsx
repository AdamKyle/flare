import React from "react";
import BasicCard from "../../../../../../../game/components/ui/cards/basic-card";
import { formatNumber } from "../../../../../../../game/lib/game/format-number";
import CharacterProfile from "../../../types/character-profile";
import TopsValue from "../../../../shared/types/tops-value";

interface TopsCharacterSummaryCardProps {
    profile: CharacterProfile;
}

export default class TopsCharacterSummaryCard extends React.Component<TopsCharacterSummaryCardProps> {
    positiveRow(
        label: string,
        value: TopsValue,
        highlighted = false,
    ): React.ReactNode {
        if (Number(value ?? 0) <= 0) {
            return null;
        }

        return (
            <React.Fragment>
                <dt>{label}:</dt>
                <dd
                    className={
                        highlighted
                            ? "text-green-700 dark:text-green-400"
                            : undefined
                    }
                >
                    {formatNumber(Number(value))}
                </dd>
            </React.Fragment>
        );
    }

    render() {
        const summary =
            this.props.profile.summary ?? this.props.profile.overview;

        return (
            <BasicCard>
                <div className="mb-4">
                    <h2 className="text-xl font-semibold">Summary</h2>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Public currency, inventory, and combat overview.
                    </p>
                </div>
                <div className="grid lg:grid-cols-2 gap-2">
                    <div>
                        <dl>
                            {this.positiveRow("Gold", summary.gold)}
                            {this.positiveRow("Gold Dust", summary.gold_dust)}
                            {this.positiveRow("Shards", summary.shards)}
                            {this.positiveRow(
                                "Copper Coins",
                                summary.copper_coins,
                            )}
                        </dl>
                    </div>
                    <div className="border-b-2 block lg:hidden border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                    <div>
                        <dl>
                            <dt>Inventory:</dt>
                            <dd>
                                {summary.inventory_count ?? 0} /{" "}
                                {summary.inventory_max ?? "?"}
                            </dd>
                            {this.positiveRow(
                                "Alchemy Bag",
                                summary.alchemy_bag_count,
                            )}
                            {this.positiveRow("Gem Bag", summary.gem_bag_count)}
                        </dl>
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                        <dl>
                            {summary.damage_stat ? (
                                <>
                                    <dt>Damage Stat:</dt>
                                    <dd>{summary.damage_stat}</dd>
                                </>
                            ) : null}
                            {this.positiveRow("To Hit", summary.to_hit, true)}
                            {this.positiveRow(
                                "Times Reincarnated",
                                typeof summary.reincarnation === "object" &&
                                    summary.reincarnation !== null &&
                                    !Array.isArray(summary.reincarnation)
                                    ? summary.reincarnation.times_reincarnated
                                    : null,
                            )}
                        </dl>
                    </div>
                </div>
            </BasicCard>
        );
    }
}
