import React from "react";
import BasicCard from "../../../../../game/components/ui/cards/basic-card";
import TopsStatList from "../../shared/components/tops-stat-list";
import ProfileSectionProps from "../types/profile-section-props";
import TopsStatListItem from "../../shared/types/tops-stat-list-item";

export default class ProfileReincarnation extends React.Component<ProfileSectionProps> {
    reincarnationItems(): TopsStatListItem[] {
        const reincarnation = this.props.reincarnation ?? {};

        return [
            {
                label: "Times Reincarnated",
                value: reincarnation.times_reincarnated,
            },
            {
                label: "Reincarnated Stat Increase",
                value: reincarnation.reincarnated_stat_increase,
            },
            { label: "XP Penalty", value: reincarnation.xp_penalty },
            { label: "Base Stat Mod", value: reincarnation.base_stat_mod },
            {
                label: "Base Damage Stat Mod",
                value: reincarnation.base_damage_stat_mod,
            },
        ];
    }

    render() {
        return (
            <BasicCard>
                <section aria-label="Reincarnation">
                    <h2 className="text-xl font-semibold">Reincarnation</h2>
                    <p className="mt-1 text-sm text-gray-700 dark:text-gray-300">
                        Current reincarnation totals only. Historical
                        reincarnation records are not shown unless a verified
                        history source exists.
                    </p>
                    <div className="mt-4">
                        <TopsStatList items={this.reincarnationItems()} />
                    </div>
                </section>
            </BasicCard>
        );
    }
}
