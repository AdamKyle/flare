import React from "react";

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
                <div className="shadow-lg rounded-lg bg-white mx-auto m-8 p-4 flex dark:bg-gray-700 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600 cursor-pointer">
                    <div className="pr-2">
                        <i className="ra ra-cycle relative top-[5px]" />
                    </div>
                    <div>
                        <div className="text-lg pb-2">Walk Kingdoms</div>
                        <div className="text-md">
                            Clicking this command will automatically send off
                            kingdoms to be walked. This can be accessed once per
                            day. Players only need to walk their kingdoms once
                            every 90 days. If a kingdom has not been walked for
                            90 or more days, the kingdom will be made into an
                            NPC kingdom, up for grabs for 30 days before
                            crumbling.
                        </div>
                    </div>
                </div>
            </div>
        );
    }
}
