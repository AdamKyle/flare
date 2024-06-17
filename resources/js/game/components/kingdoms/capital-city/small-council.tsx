import React from "react";
import ClickableIconCard from "../../ui/cards/clickable-icon-card";

export default class SmallCouncil extends React.Component<any, any> {
    constructor(props: any) {
        super(props);

        this.state = {};
    }

    render() {
        return (
            <div>
                <h3>Oversee your kingdoms</h3>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>
                <p className={"my-2"}>
                    Below you can manage various aspects of all kingdoms that
                    are on the same plane as this one. Because you have stated
                    this is your capital city, you can send out orders to
                    upgrade, repair, recruit and walk kingdoms.
                </p>
                <p className={"my-2 text-blue-500 dark:text-blue-300"}>
                    To begin, read the cards below. Clicking the card can either
                    send off an action or open a new view for you to choose what
                    to do.
                </p>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>
                <div className="border-2 border-gray-500 dark:border-gray-600 bg-gray-700 dark:bg-gray-600 mr-auto ml-auto p-4 rounded shadow-lg">
                    <ClickableIconCard
                        title={"Walk Kingdoms"}
                        icon_class={"ra ra-cycle"}
                        on_click={() => {}}
                    >
                        <p className="mb-2">
                            Clicking this command will automatically send off
                            kingdoms to be walked. This can be accessed once per
                            day. Players only need to walk their kingdoms once
                            every 90 days. If a kingdom has not been walked for
                            90 or more days, the kingdom will be made into an
                            NPC kingdom, up for grabs for 30 days before
                            crumbling.
                        </p>

                        {this.props.has_been_walked ? (
                            <p className="text-red-500 dark:text-red-300">
                                You have already walked your kingdoms today. You
                                can do so again tomorrow.
                            </p>
                        ) : null}
                    </ClickableIconCard>
                    <ClickableIconCard
                        title={"Upgrade/Repair Buildings"}
                        icon_class={"ra ra-heart-tower"}
                        on_click={() => {}}
                    >
                        Clicking this card will allow you to see two lists of
                        buildings: Those that need to be repaired and those that
                        can be upgraded. A building can be upgraded if it does
                        not need to be repaired, is unlocked and is not max
                        level. With that in hand, we have already filtered the
                        buildings you can upgrade across all your kingdoms on
                        this plane.
                    </ClickableIconCard>
                    <ClickableIconCard
                        title={"Recruit Units"}
                        icon_class={"ra ra-crossed-swords"}
                        on_click={() => {}}
                    >
                        Clicking this card will allow you to recruit units
                        across all your kingdoms. You can specify which kingdoms
                        get what units, or recruit units across all kingdoms. A
                        unit can be recruited if you have not met the max amount
                        of that unit and have the unit unlocked. As a result
                        units you can recruit have been filtered.
                    </ClickableIconCard>
                </div>
            </div>
        );
    }
}
