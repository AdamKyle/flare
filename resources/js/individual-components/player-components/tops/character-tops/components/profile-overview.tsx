import React from "react";
import BasicCard from "../../../../../game/components/ui/cards/basic-card";
import TopsStatList from "../../shared/components/tops-stat-list";
import ProfileSectionProps from "../types/profile-section-props";
import { asTopsRecord } from "../../shared/helpers/tops-value-helpers";
import TopsStatListItem from "../../shared/types/tops-stat-list-item";
import TopsValue from "../../shared/types/tops-value";

export default class ProfileOverview extends React.Component<ProfileSectionProps> {
    overview(): Record<string, TopsValue> {
        return this.props.overview ?? {};
    }

    identityItems(): TopsStatListItem[] {
        const overview = this.overview();

        return [
            { label: "Character Name", value: overview.name },
            { label: "Race", value: overview.race },
            { label: "Class", value: overview.class },
            { label: "Current Map", value: overview.current_map },
            {
                label: "Online Status",
                value: overview.online ? "Online" : "Offline",
            },
            { label: "Last Active", value: overview.last_active_at },
        ];
    }

    progressionItems(): TopsStatListItem[] {
        const overview = this.overview();

        return [
            { label: "Level", value: overview.level },
            { label: "XP", value: overview.xp },
            { label: "XP to Next Level", value: overview.xp_next },
            { label: "Kingdom Count", value: overview.kingdom_count },
        ];
    }

    currencyItems(): TopsStatListItem[] {
        const overview = this.overview();

        return [
            { label: "Gold", value: overview.gold },
            { label: "Shards", value: overview.shards },
            { label: "Copper Coins", value: overview.copper_coins },
            { label: "Gold Dust", value: overview.gold_dust },
        ];
    }

    inventoryItems(): TopsStatListItem[] {
        const overview = this.overview();

        return [
            { label: "Inventory Count", value: overview.inventory_count },
            { label: "Inventory Max", value: overview.inventory_max },
            { label: "Gem Bag Count", value: overview.gem_bag_count },
            { label: "Alchemy Bag Count", value: overview.alchemy_bag_count },
        ];
    }

    combatItems(): TopsStatListItem[] {
        const overview = this.overview();
        const automationStatus = asTopsRecord(overview.automation_status);

        return [
            { label: "Damage Stat", value: overview.damage_stat },
            { label: "To-Hit Stat", value: overview.to_hit_stat },
            {
                label: "Auto Battling",
                value: automationStatus.auto_battling ? "Running" : "Stopped",
            },
            {
                label: "Faction Loyalty Automation",
                value: automationStatus.faction_loyalty_running
                    ? "Running"
                    : "Stopped",
            },
        ];
    }

    render() {
        return (
            <section
                className="grid gap-4 lg:grid-cols-2"
                aria-label="Overview"
            >
                <BasicCard>
                    <h2 className="text-xl font-semibold">Identity</h2>
                    <div className="mt-4">
                        <TopsStatList items={this.identityItems()} />
                    </div>
                </BasicCard>
                <BasicCard>
                    <h2 className="text-xl font-semibold">Progression</h2>
                    <div className="mt-4">
                        <TopsStatList items={this.progressionItems()} />
                    </div>
                </BasicCard>
                <BasicCard>
                    <h2 className="text-xl font-semibold">Currencies</h2>
                    <div className="mt-4">
                        <TopsStatList items={this.currencyItems()} />
                    </div>
                </BasicCard>
                <BasicCard>
                    <h2 className="text-xl font-semibold">Inventory</h2>
                    <div className="mt-4">
                        <TopsStatList items={this.inventoryItems()} />
                    </div>
                </BasicCard>
                <BasicCard additionalClasses="lg:col-span-2">
                    <h2 className="text-xl font-semibold">Combat Summary</h2>
                    <div className="mt-4">
                        <TopsStatList items={this.combatItems()} />
                    </div>
                </BasicCard>
            </section>
        );
    }
}
