import React from "react";
import Dialogue from "../../../components/ui/dialogue/dialogue";

export default class SpecialtyActionsHelpModal extends React.Component<
    any,
    any
> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.handle_close}
                title={"Specialty Actions Help"}
            >
                <div className=" overflow-y-auto max-h-[450px] lg:max-h-[800px]">
                    <div className="my-4">
                        <h3 className="my-3">Smelting</h3>
                        <p className="mb-2">
                            Smelting is unlocked by completing a quest line in
                            Hell starting with the quest: Story of the Red
                            Hawks. This will then unlock the Passive:
                            Blacksmith's Furnace, which after leveling it to
                            level one, will then unlock the Blacksmith's Furnace
                            building.
                        </p>

                        <p>
                            Once this building is then level 6, you can use the
                            Smelter to smelt iron into steel which is then used
                            to build the Airship Fields - who in turn let you
                            recruit Airships.
                        </p>
                    </div>

                    <div className="my-4">
                        <h3 className="my-3">Manage Gold Bars</h3>
                        <p className="mb-2">
                            Gold bars are a way for you to store excess gold in
                            the kingdom treasury. You can do this by unlocking
                            and leveling the Goblin Coin Bank passive, then
                            leveling the building to level 5.
                        </p>

                        <p>
                            Each gold bar costs 1 billion gold. You can store
                            1000 gold bars for a total of 2 Trillion Gold. This
                            will also increase your kingdom defence by 1% for
                            every ~10 gold bars to a maximum of 100%.
                        </p>
                    </div>

                    <div className="my-4">
                        <h3 className="my-3">Resource Request</h3>
                        <p className="mb-2">
                            Allows you to request resources from a single
                            kingdom. If players select to use an Airship, the
                            amount of resources to be transferred can be 10,000
                            of any or all types. The base amount is 5,000.
                            Players must own a Market Place in both kingdoms
                            which can be unlocked via a passive skill called
                            "Moving Resources".
                        </p>
                    </div>

                    <div className="my-4">
                        <h3 className="my-3">Capital City</h3>
                        <p className="mb-2">
                            Players can create a Capital City by selecting one
                            kingdom for each plane, to be their capital city.
                            From here, the button will change to be called:
                            "Chancellors Quarters". This allows players to
                            manage various aspects of all kingdoms on the same
                            plane as that capital city, from upgrading,
                            repeating buildings and recruiting units and
                            instruct them to be walked, so they don't become
                            abandoned.
                        </p>
                        <p className="mb-2">
                            Players must unlock the passive: "Creating a Realm"
                            and level it up to level one after they complete a
                            small quest line called: "The Realm is Mine" which
                            starts in Dungeons.
                        </p>
                    </div>
                </div>
            </Dialogue>
        );
    }
}
