import React, { ReactNode } from "react";
import FactionNpcSectionProps from "./types/faction-npc-section-props";
import OrangeProgressBar from "../../components/ui/progress-bars/orange-progress-bar";
import { FameTasks } from "./deffinitions/faction-loaylaty";

export default class FactionNpcTasks extends React.Component<
    FactionNpcSectionProps,
    {}
> {
    constructor(props: any) {
        super(props);
    }

    showCheckMark(fameTask: FameTasks): ReactNode {
        if (fameTask.current_amount === fameTask.required_amount) {
            return (
                <i className="fas fa-check text-green-700 dark:text-green-500"></i>
            );
        }

        return;
    }

    renderTasks(fameTasks: FameTasks[], bounties: boolean) {
        return fameTasks
            .filter((fameTask: FameTasks) => {
                return bounties
                    ? fameTask.type === "bounty"
                    : fameTask.type !== "bounty";
            })
            .map((fameTask: FameTasks) => {
                return (
                    <>
                        <dt>
                            {bounties
                                ? fameTask.monster_name
                                : fameTask.item_name +
                                  " [" +
                                  fameTask.type +
                                  "]"}
                        </dt>
                        <dd>
                            {this.showCheckMark(fameTask)}{" "}
                            {fameTask.current_amount} /{" "}
                            {fameTask.required_amount}
                        </dd>
                    </>
                );
            });
    }

    render() {
        return (
            <>
                <div>
                    <OrangeProgressBar
                        primary_label={
                            this.props.faction_loyalty_npc.npc.real_name +
                            " Fame LV: " +
                            this.props.faction_loyalty_npc.current_level +
                            "/" +
                            this.props.faction_loyalty_npc.max_level
                        }
                        secondary_label={
                            this.props.faction_loyalty_npc.current_fame +
                            "/" +
                            this.props.faction_loyalty_npc.next_level_fame +
                            " Fame"
                        }
                        percentage_filled={
                            (this.props.faction_loyalty_npc.current_fame /
                                this.props.faction_loyalty_npc
                                    .next_level_fame) *
                            100
                        }
                        push_down={false}
                    />
                </div>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                <div>
                    <div>
                        <h3 className="my-2"> Bounties </h3>
                        <dl>
                            {this.renderTasks(
                                this.props.faction_loyalty_npc
                                    .faction_loyalty_npc_tasks.fame_tasks,
                                true,
                            )}
                        </dl>
                    </div>
                    <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                    <div>
                        <h3 className="my-2"> Crafting </h3>
                        <dl>
                            {this.renderTasks(
                                this.props.faction_loyalty_npc
                                    .faction_loyalty_npc_tasks.fame_tasks,
                                false,
                            )}
                        </dl>
                    </div>
                </div>
                <p className="my-4">
                    Bounties must be completed on the respective plane and
                    manually. Automation will not work for this.
                </p>
            </>
        );
    }
}
