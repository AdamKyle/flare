import React from "react";
import BasicCard from "../../../../../../../game/components/ui/cards/basic-card";
import Tabs from "../../../../../../../game/components/ui/tabs/tabs";
import TabPanel from "../../../../../../../game/components/ui/tabs/tab-panel";
import OrangeButton from "../../../../../../../game/components/ui/buttons/orange-button";
import { formatNumber } from "../../../../../../../game/lib/game/format-number";
import { formatLocalDateTime } from "../../../../../../../game/lib/game/format-local-date";
import CharacterProfile from "../../../types/character-profile";
import TopsValue from "../../../../shared/types/tops-value";
import { asTopsRecordList } from "../../../../shared/helpers/tops-value-helpers";

interface TopsCharacterInfoTabsProps {
    profile: CharacterProfile;
    manage_addition_data: () => void;
}

const INFO_TABS = [
    { key: "info", name: "Info" },
    { key: "boons", name: "Active Boons" },
    { key: "factions", name: "Factions" },
];

class FactionsList extends React.Component<{
    factions: Record<string, TopsValue>;
}> {
    render() {
        const factions = this.props.factions;
        const list = asTopsRecordList(factions.factions).filter(
            (faction) =>
                (faction.current_level ?? 0) > 1 ||
                (faction.current_points ?? 0) > 0 ||
                faction.maxed === true,
        );
        const npcs = asTopsRecordList(factions.npcs).filter(
            (npc) =>
                (npc.current_level ?? 0) > 1 ||
                npc.currently_helping === true ||
                (npc.next_level_fame ?? 0) > 0,
        );

        if (list.length === 0 && npcs.length === 0) {
            return (
                <p className="mt-2 text-sm text-gray-700 dark:text-gray-300">
                    No faction data available.
                </p>
            );
        }

        return (
            <dl className="mt-2 grid grid-cols-[minmax(0,1fr)_auto] gap-x-3 gap-y-2">
                {list.map((faction, index: number) => (
                    <React.Fragment key={`faction-${index}`}>
                        <dt className="font-medium">
                            {faction.map ?? "Unknown Map"}
                        </dt>
                        <dd>
                            Level {faction.current_level ?? 0}
                            {faction.maxed
                                ? " — Maxed"
                                : ` — ${formatNumber(faction.current_points ?? 0)} / ${formatNumber(faction.points_needed ?? 0)} pts`}
                        </dd>
                    </React.Fragment>
                ))}
                {npcs.map((npc, index: number) => (
                    <React.Fragment key={`npc-${index}`}>
                        <dt className="font-medium">
                            {npc.npc_name ?? "Unknown NPC"}
                        </dt>
                        <dd>
                            Level {npc.current_level ?? 0}
                            {npc.currently_helping
                                ? " — Currently helping"
                                : ""}
                            {(npc.next_level_fame ?? 0) > 0
                                ? ` — Next level fame: ${formatNumber(npc.next_level_fame)}`
                                : ""}
                        </dd>
                    </React.Fragment>
                ))}
            </dl>
        );
    }
}

function PositiveNumberRow({
    label,
    value,
}: {
    label: string;
    value: TopsValue;
}) {
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

export default class TopsCharacterInfoTabs extends React.Component<TopsCharacterInfoTabsProps> {
    render() {
        const info = {
            ...(this.props.profile.overview ?? {}),
            ...(this.props.profile.info ?? {}),
        };
        const factions = this.props.profile.factions ?? {};

        return (
            <BasicCard>
                <Tabs tabs={INFO_TABS} full_width={true}>
                    <TabPanel key="info">
                        <div className="grid md:grid-cols-2 gap-2">
                            <div>
                                <dl>
                                    <dt>Name:</dt>
                                    <dd>{info.name ?? "Unknown"}</dd>
                                    <dt>Race:</dt>
                                    <dd>{info.race ?? "Unknown"}</dd>
                                    <dt>Class:</dt>
                                    <dd>{info.class ?? "Unknown"}</dd>
                                    <dt>Level:</dt>
                                    <dd>{info.level ?? 0}</dd>
                                    <dt>Status:</dt>
                                    <dd>
                                        {info.online ? "Online" : "Offline"}
                                    </dd>
                                    <dt>Current Map:</dt>
                                    <dd>{info.current_map ?? "Unknown"}</dd>
                                    <dt>Last Active:</dt>
                                    <dd>
                                        {formatLocalDateTime(
                                            info.last_active_at,
                                        )}
                                    </dd>
                                </dl>
                            </div>
                            <div className="border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                            <div>
                                <dl>
                                    <PositiveNumberRow
                                        label="Max Health"
                                        value={info.max_health}
                                    />
                                    <PositiveNumberRow
                                        label="Total Attack"
                                        value={info.total_attack}
                                    />
                                    <PositiveNumberRow
                                        label="Heal For"
                                        value={info.heal_for}
                                    />
                                    <PositiveNumberRow
                                        label="AC"
                                        value={info.ac}
                                    />
                                </dl>
                            </div>
                        </div>
                        <div className="border-b-2 block border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                        <div className="flex flex-wrap justify-center gap-2 lg:flex-nowrap text-center">
                            <div className="mt-4 w-full lg:w-auto">
                                <OrangeButton
                                    button_label={"Show additional details"}
                                    on_click={this.props.manage_addition_data}
                                />
                            </div>
                        </div>
                        <div className="relative top-[24px]">
                            <div className="flex justify-between mb-1">
                                <span className="font-medium text-orange-700 dark:text-white text-xs">
                                    XP
                                </span>
                                <span className="text-xs font-medium text-orange-700 dark:text-white">
                                    {formatNumber(info.xp ?? 0)}/
                                    {formatNumber(info.xp_next ?? 0)}
                                </span>
                            </div>
                            <div className="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                                <div
                                    className="bg-orange-600 h-1.5 rounded-full"
                                    style={{
                                        width:
                                            Math.min(
                                                100,
                                                Math.floor(
                                                    ((info.xp ?? 0) /
                                                        Math.max(
                                                            1,
                                                            info.xp_next ?? 1,
                                                        )) *
                                                        100,
                                                ),
                                            ) + "%",
                                    }}
                                ></div>
                            </div>
                        </div>
                    </TabPanel>
                    <TabPanel key="boons">
                        <p className="mt-2 text-sm text-gray-700 dark:text-gray-300">
                            No public active boon details are available.
                        </p>
                    </TabPanel>
                    <TabPanel key="factions">
                        <FactionsList factions={factions} />
                    </TabPanel>
                </Tabs>
            </BasicCard>
        );
    }
}
