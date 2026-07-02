import React from "react";
import BasicCard from "../../../../../../game/components/ui/cards/basic-card";
import { formatTopsValue } from "../../../shared/helpers/tops-format-value";
import {
    asTopsRecord,
    asTopsRecordList,
} from "../../../shared/helpers/tops-value-helpers";
import TopsValue from "../../../shared/types/tops-value";
import CharacterProfileSidePanelProps from "../../types/profile/character-profile-side-panel-props";

export default class CharacterProfileSidePanel extends React.Component<CharacterProfileSidePanelProps> {
    overview(): Record<string, TopsValue> {
        return this.props.profile.overview ?? {};
    }

    reincarnation(): Record<string, TopsValue> {
        return asTopsRecord(this.props.profile.reincarnation);
    }

    activity(): Record<string, TopsValue> {
        return asTopsRecord(this.props.profile.activity);
    }

    renderMetric(label: string, value: TopsValue | undefined) {
        return (
            <div className="rounded-sm border border-gray-200 p-3 dark:border-gray-700">
                <p className="text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">
                    {label}
                </p>
                <p className="mt-1 font-bold text-gray-900 dark:text-gray-100">
                    {formatTopsValue(value)}
                </p>
            </div>
        );
    }

    render() {
        const overview = this.overview();
        const reincarnation = this.reincarnation();
        const activity = this.activity();
        const equipment = asTopsRecord(this.props.profile.equipment);
        const items = asTopsRecordList(equipment.items);

        return (
            <BasicCard>
                <aside aria-label="Character profile summary">
                    <h2 className="text-xl font-semibold">Summary</h2>
                    <div className="mt-4 grid gap-3">
                        {this.renderMetric("Gold", overview.gold)}
                        {this.renderMetric("Gold Dust", overview.gold_dust)}
                        {this.renderMetric("Shards", overview.shards)}
                        {this.renderMetric(
                            "Copper Coins",
                            overview.copper_coins,
                        )}
                        {this.renderMetric(
                            "Inventory",
                            String(overview.inventory_count ?? 0) +
                                " / " +
                                String(overview.inventory_max ?? 0),
                        )}
                        {this.renderMetric("Worn Items", items.length)}
                        {this.renderMetric(
                            "Reincarnations",
                            reincarnation.times_reincarnated,
                        )}
                        {this.renderMetric("Kingdoms", overview.kingdom_count)}
                        {this.renderMetric(
                            "Exploration Runs",
                            activity.exploration_run_count,
                        )}
                    </div>
                </aside>
            </BasicCard>
        );
    }
}
