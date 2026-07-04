import React from "react";
import BasicCard from "../../../../../../../game/components/ui/cards/basic-card";
import { formatNumber } from "../../../../../../../game/lib/game/format-number";

function NeutralPositiveRow({ label, value }: { label: string; value: any }) {
    if (Number(value ?? 0) <= 0) {
        return null;
    }

    return (
        <>
            <dt>{label}:</dt>
            <dd>{formatNumber(value)}</dd>
        </>
    );
}

function StatPositiveRow({ label, value }: { label: string; value: any }) {
    if (Number(value ?? 0) <= 0) {
        return null;
    }

    return (
        <>
            <dt>{label}:</dt>
            <dd className="text-green-700 dark:text-green-400">
                {formatNumber(value)}
            </dd>
        </>
    );
}

export default function TopsCharacterSummaryCard({
    profile,
}: {
    profile: any;
}) {
    const summary = profile.summary ?? profile.overview ?? {};

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
                        <NeutralPositiveRow label="Gold" value={summary.gold} />
                        <NeutralPositiveRow
                            label="Gold Dust"
                            value={summary.gold_dust}
                        />
                        <NeutralPositiveRow
                            label="Shards"
                            value={summary.shards}
                        />
                        <NeutralPositiveRow
                            label="Copper Coins"
                            value={summary.copper_coins}
                        />
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
                        <NeutralPositiveRow
                            label="Alchemy Bag"
                            value={summary.alchemy_bag_count}
                        />
                        <NeutralPositiveRow
                            label="Gem Bag"
                            value={summary.gem_bag_count}
                        />
                    </dl>
                    <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                    <dl>
                        {summary.damage_stat ? (
                            <>
                                <dt>Damage Stat:</dt>
                                <dd>{summary.damage_stat}</dd>
                            </>
                        ) : null}
                        <StatPositiveRow
                            label="To Hit"
                            value={summary.to_hit}
                        />
                        <NeutralPositiveRow
                            label="Times Reincarnated"
                            value={summary.reincarnation?.times_reincarnated}
                        />
                    </dl>
                </div>
            </div>
        </BasicCard>
    );
}
