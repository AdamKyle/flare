import React from "react";
import { formatNumber } from "../../lib/game/format-number";
import FactionNpcSectionProps from "./types/faction-npc-section-props";

export default class FactionNpcSection extends React.Component<
    FactionNpcSectionProps,
    {}
> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <>
                <h4 className="text-lg font-semibold mb-2">
                    Rewards (when fame levels up)
                </h4>
                <dl className="my-2 grid grid-cols-2 gap-x-2 gap-y-1 text-sm sm:text-base w-1/2 md:w-auto">
                    <dt className="font-medium">XP</dt>
                    <dd>
                        {formatNumber(
                            this.props.faction_loyalty_npc.current_level > 0
                                ? this.props.faction_loyalty_npc.current_level *
                                      1000
                                : 1000,
                        )}
                    </dd>
                    <dt className="font-medium">Gold</dt>
                    <dd>
                        {formatNumber(
                            this.props.faction_loyalty_npc.current_level > 0
                                ? this.props.faction_loyalty_npc.current_level *
                                      1000000
                                : 1000000,
                        )}
                    </dd>
                    <dt className="font-medium">Gold Dust</dt>
                    <dd>
                        {formatNumber(
                            this.props.faction_loyalty_npc.current_level > 0
                                ? this.props.faction_loyalty_npc.current_level *
                                      1000
                                : 1000,
                        )}
                    </dd>
                    <dt className="font-medium">Shards</dt>
                    <dd>
                        {formatNumber(
                            this.props.faction_loyalty_npc.current_level > 0
                                ? this.props.faction_loyalty_npc.current_level *
                                      1000
                                : 1000,
                        )}
                    </dd>
                    <dt className="font-medium">Item Reward</dt>
                    <dd>
                        <a
                            href="/information/random-enchants"
                            target="_blank"
                            className="text-blue-500 underline"
                        >
                            Unique Item{" "}
                            <i className="fas fa-external-link-alt"></i>
                        </a>
                    </dd>
                </dl>

                <h4 className="text-lg font-semibold mt-4 mb-2">
                    Kingdom Item Defence Bonus
                </h4>
                <p className="my-4 text-sm sm:text-base w-1/2 md:w-auto">
                    Slowly accumulates as you level this NPC's fame. Stacks with
                    other NPCs on the same plane to a max of 95%.
                </p>
                <dl className="grid grid-cols-2 gap-x-2 gap-y-1 text-sm sm:text-base w-1/2 md:w-auto">
                    <dt className="font-medium">Defence Bonus per level</dt>
                    <dd>
                        {(
                            this.props.faction_loyalty_npc
                                .kingdom_item_defence_bonus * 100
                        ).toFixed(2)}
                        %
                    </dd>
                    <dt className="font-medium">Current Defence Bonus</dt>
                    <dd>
                        {(
                            this.props.faction_loyalty_npc
                                .current_kingdom_item_defence_bonus * 100
                        ).toFixed(0)}
                        %
                    </dd>
                </dl>
            </>
        );
    }
}
