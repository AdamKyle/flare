import React from "react";
import BasicCard from "../../../../../game/components/ui/cards/basic-card";
import TopsStatList from "../../shared/components/tops-stat-list";
import TopsEmptyState from "../../shared/components/tops-empty-state";
import ProfileSectionProps from "../types/profile-section-props";
import TopsValue from "../../shared/types/tops-value";
import {
    asTopsRecord,
    asTopsRecordList,
} from "../../shared/helpers/tops-value-helpers";
import TopsStatListItem from "../../shared/types/tops-stat-list-item";

export default class ProfileKingdoms extends React.Component<ProfileSectionProps> {
    kingdoms(): Record<string, TopsValue> {
        return this.props.kingdoms ?? {};
    }

    resourceTotals(): Record<string, TopsValue> {
        return asTopsRecord(this.kingdoms().resource_totals);
    }

    mapDistribution(): Record<string, TopsValue> {
        return asTopsRecord(this.kingdoms().map_distribution);
    }

    kingdomSummaryItems(): TopsStatListItem[] {
        const kingdoms = this.kingdoms();

        return [
            { label: "Kingdom Count", value: kingdoms.kingdom_count },
            { label: "Capital Count", value: kingdoms.capital_count },
            { label: "Total Treasury", value: kingdoms.total_treasury },
            { label: "Total Gold Bars", value: kingdoms.total_gold_bars },
            { label: "Population Total", value: kingdoms.population_total },
            { label: "Average Morale", value: kingdoms.morale_average },
        ];
    }

    resourceItems(): TopsStatListItem[] {
        const resourceTotals = this.resourceTotals();

        return [
            { label: "Stone", value: resourceTotals.stone },
            { label: "Wood", value: resourceTotals.wood },
            { label: "Clay", value: resourceTotals.clay },
            { label: "Iron", value: resourceTotals.iron },
            { label: "Steel", value: resourceTotals.steel },
        ];
    }

    populationItems(): TopsStatListItem[] {
        const kingdoms = this.kingdoms();

        return [
            { label: "Population Total", value: kingdoms.population_total },
            { label: "Average Morale", value: kingdoms.morale_average },
        ];
    }

    mapDistributionItems(): TopsStatListItem[] {
        const mapDistribution = this.mapDistribution();

        return Object.keys(mapDistribution).map((key: string) => ({
            label: key,
            value: mapDistribution[key],
        }));
    }

    renderKingdom(kingdom: Record<string, TopsValue>) {
        const items: TopsStatListItem[] = [
            { label: "Map", value: kingdom.map },
            {
                label: "Capital",
                value: kingdom.is_capital ? "Yes" : "No",
            },
            { label: "Treasury", value: kingdom.treasury },
            { label: "Gold Bars", value: kingdom.gold_bars },
            { label: "Population", value: kingdom.current_population },
            { label: "Morale", value: kingdom.current_morale },
        ];

        return (
            <article
                key={String(kingdom.id)}
                className="rounded-sm border border-gray-200 p-3 dark:border-gray-700"
            >
                <h3 className="font-semibold">
                    {kingdom.name ?? "Unknown Kingdom"}
                </h3>
                <TopsStatList items={items} />
            </article>
        );
    }

    render() {
        const kingdomRows = asTopsRecordList(this.kingdoms().kingdoms);

        return (
            <section
                className="grid gap-4 lg:grid-cols-2"
                aria-label="Kingdoms"
            >
                <BasicCard>
                    <h2 className="text-xl font-semibold">Kingdom Summary</h2>
                    <div className="mt-4">
                        <TopsStatList items={this.kingdomSummaryItems()} />
                    </div>
                </BasicCard>
                <BasicCard>
                    <h2 className="text-xl font-semibold">Resource Totals</h2>
                    <div className="mt-4">
                        <TopsStatList items={this.resourceItems()} />
                    </div>
                </BasicCard>
                <BasicCard>
                    <h2 className="text-xl font-semibold">Map Distribution</h2>
                    <div className="mt-4">
                        <TopsStatList items={this.mapDistributionItems()} />
                    </div>
                </BasicCard>
                <BasicCard>
                    <h2 className="text-xl font-semibold">Top Kingdoms</h2>
                    <div className="mt-4 grid gap-3">
                        {kingdomRows.length === 0 ? (
                            <TopsEmptyState message="No player-owned kingdoms are available for this character." />
                        ) : (
                            kingdomRows.map(
                                (kingdom: Record<string, TopsValue>) =>
                                    this.renderKingdom(kingdom),
                            )
                        )}
                    </div>
                </BasicCard>
            </section>
        );
    }
}
