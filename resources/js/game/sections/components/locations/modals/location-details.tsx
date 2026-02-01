import React, { Fragment, ReactNode } from "react";
import { formatNumber } from "../../../../lib/game/format-number";
import SpecialLocationHelpModal from "./special-location-help-modal";
import LocationDetailsProps from "../../../map/types/map/location-pins/modals/location-details-props";
import WarningAlert from "../../../../components/ui/alerts/simple-alerts/warning-alert";
import InfoAlert from "../../../../components/ui/alerts/simple-alerts/info-alert";

export default class LocationDetails extends React.Component<
    LocationDetailsProps,
    any
> {
    constructor(props: LocationDetailsProps) {
        super(props);

        this.state = {
            open_help_dialogue: false,
        };
    }

    manageHelpDialogue() {
        this.setState({
            open_help_dialogue: !this.state.open_help_dialogue,
        });
    }

    isSpecialLocation(): boolean {
        return this.props.location.increase_enemy_percentage_by !== null;
    }

    renderSpecialType() {
        if (this.props.location.type_name === "Gold Mines") {
            return (
                <Fragment>
                    <div className="border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block"></div>
                    <h5 className="text-orange-500 dark:text-orange-400">
                        Gold Mines!
                    </h5>
                    <WarningAlert additional_css={"my-4"}>
                        Exploration cannot be used here if you want the below
                        rewards. You must manually fight. Except for currencies.
                        You can explore here to gain the currencies.
                    </WarningAlert>
                    <p className="my-4">
                        Welcome to the Gold Mines, a special mid game location
                        to help players start farming currencies for end game
                        gear while they continue their questing to unlock more
                        of the game and work further towards the true power of
                        their character! Come now child, death awaits!
                    </p>
                    <ul className="list-disc">
                        <li className="ml-4">
                            Characters can get 1-10,000 Gold from fighting
                            monsters. This can be increased to 20,000 if an
                            event is triggered at this area.
                        </li>
                        <li className="ml-4">
                            Characters can get 1-500 Gold Dust from fighting
                            monsters. This can be increased to 1,000 if an event
                            is triggered at this area.
                        </li>
                        <li className="ml-4">
                            Characters can get 1-500 Shards from fighting
                            monsters. This can be increased to 1,000 if an event
                            is triggered at this area.
                        </li>
                        <li className="ml-4">
                            There is a 1/1,000,000 (+15% Looting) chance to get
                            a random{" "}
                            <a
                                href="/information/random-enchants"
                                target="_blank"
                            >
                                Medium Unique{" "}
                                <i className="fas fa-external-link-alt"></i>
                            </a>{" "}
                            from Monsters half way down the list of more. This
                            can be reduced to 1/500,000 (+30% Looting) chance if
                            an event is triggered at this area.
                        </li>
                        <li className="ml-4">
                            There is a 1/1,000,000 chance to trigger an event
                            while fighting here to reduce the chances and
                            increase the currencies (the above "if an event is
                            triggered") for 1 hour at this location only.
                        </li>
                    </ul>
                </Fragment>
            );
        }

        if (this.props.location.type_name === "The Old Church") {
            return (
                <Fragment>
                    <div className="border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block"></div>
                    <h5 className="text-orange-500 dark:text-orange-400">
                        The Old Church!
                    </h5>
                    <WarningAlert additional_css={"my-4"}>
                        Exploration cannot be used here if you want the below
                        rewards. You must manually fight. Except for currencies.
                        You can explore here to gain the currencies.
                    </WarningAlert>

                    <InfoAlert additional_css={"my-4 font-bold"}>
                        The below only applies to those who poses the Christmas
                        Tree Light Bulb Quest item from completing a quest chain
                        that starts with: Thousands of Years Ago ... and ends
                        with: The doors to The Old Church.
                    </InfoAlert>
                    <p className="my-4">
                        Welcome to the The Old Church, a special mid game
                        location to help players start farming currencies for
                        end game gear while they continue their questing to
                        unlock more of the game and work further towards the
                        true power of their character! Come now child, death
                        awaits!
                    </p>
                    <ul className="list-disc my-4">
                        <li className="ml-4">
                            Characters can get 1-1000 Gold Dust from fighting
                            monsters. This can be increased to 5,000 if an event
                            is triggered at this area.
                        </li>
                        <li className="ml-4">
                            Characters can get 1-1000 Shards from fighting
                            monsters. This can be increased to 5,000 if an event
                            is triggered at this area.
                        </li>
                        <li className="ml-4">
                            Characters can get 1-20,000 Gold from fighting
                            monsters. This can be increased to 40,000 if an
                            event is triggered at this area.
                        </li>
                        <li className="ml-4">
                            There is a 1/1,000 chance (+15% of your looting) to
                            get a{" "}
                            <a href="/information/unique-items" target="_blank">
                                Unique{" "}
                                <i className="fas fa-external-link-alt"></i>
                            </a>{" "}
                            Corrupted Ice from Monsters halfway down the list of
                            more. This can be reduced to 1/500 (+30% Looting)
                            chance if an event is triggered at this area.
                        </li>
                        <li className="ml-4">
                            There is a 1/1,000 chance to trigger an event while
                            fighting here to reduce the chances and increase the
                            currencies (the above "if an event is triggered")
                            for 1 hour at this location only.
                        </li>
                    </ul>
                </Fragment>
            );
        }

        if (this.props.location.type_name === "Purgatory Dungeons") {
            return (
                <Fragment>
                    <div className="border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block"></div>
                    <h5 className="text-orange-500 dark:text-orange-400">
                        Purgatory Dungeons!
                    </h5>
                    <p className="my-4">
                        You have entered into the Purgatory Dungeons. You{" "}
                        <strong>can explore here</strong>. This is the only
                        place known to drop{" "}
                        <a href="/information/mythical-items" target="_blank">
                            Mythic Items{" "}
                            <i className="fas fa-external-link-alt"></i>
                        </a>
                        .
                    </p>
                </Fragment>
            );
        }

        if (this.props.location.type_name === "Purgatory Smiths House") {
            return (
                <Fragment>
                    <div className="border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block"></div>
                    <h5 className="text-orange-500 dark:text-orange-400">
                        Purgatory Smith House!
                    </h5>
                    <WarningAlert additional_css={"my-4"}>
                        Exploration cannot be used here if you want the below
                        rewards. You must manually fight. Except for currencies.
                        You can explore here to gain the currencies.
                    </WarningAlert>
                    <p className="mb-4">
                        In this location, a few things will happen for those who
                        have access:
                    </p>
                    <ul className="list-disc">
                        <li className="ml-4">
                            Characters can get 1-1000 Gold Dust from fighting
                            monsters. This can be increased to 5,000 if an event
                            is triggered at this area.
                        </li>
                        <li className="ml-4">
                            Characters can get 1-1000 Shards from fighting
                            monsters. This can be increased to 5,000 if an event
                            is triggered at this area.
                        </li>
                        <li className="ml-4">
                            Characters can get 1-1000 Copper Coins<sup>*</sup>{" "}
                            from fighting monsters. This can be increased to
                            5,000 if an event is triggered at this area.
                        </li>
                        <li className="ml-4">
                            There is a 1/1,000,000 chance to get a Purgatory
                            Chain{" "}
                            <a
                                href="/information/random-enchants"
                                target="_blank"
                            >
                                Legendary Unique{" "}
                                <i className="fas fa-external-link-alt"></i>
                            </a>{" "}
                            from Monsters half way down the list of more. This
                            can be reduced to 1/500,000 chance if an event is
                            triggered at this area.
                        </li>
                        <li className="ml-4">
                            There is a 1/10,000,000 chance to get a Purgatory
                            Chain{" "}
                            <a
                                href="/information/mythical-items"
                                target="_blank"
                            >
                                Mythic Items{" "}
                                <i className="fas fa-external-link-alt"></i>
                            </a>{" "}
                            from the last monster in the list. This can be
                            reduced to 1/5,000,000 chance if an event is
                            triggered at this area.
                        </li>
                        <li className="ml-4">
                            There is a 1/1,000,000 chance to trigger an event
                            while fighting here to reduce the chances and
                            increase the currencies (the above "if an event is
                            triggered") for 1 hour at this location only.
                        </li>
                    </ul>
                    <p className="mt-4 mb-4 italic">
                        <sup>*</sup> Provided characters have the required quest
                        item to obtain copper coins.
                    </p>
                </Fragment>
            );
        }

        return null;
    }

    renderRaidDetails() {
        if (this.props.location.is_corrupted) {
            return (
                <Fragment>
                    <div className="border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block"></div>
                    <h5 className="text-orange-500 dark:text-orange-400">
                        Corrupted
                    </h5>
                    <p className="my-4">
                        This location has been corrupted by evil forces! There
                        happens to be a raid going on here, the monsters of this
                        location are exceptionally hard, how ever quest items
                        that you would get for visiting the place, if
                        applicable, will still drop.
                    </p>
                    {this.props.location.has_raid_boss ? (
                        <p className="my-4 font-bold">
                            The raid boss lives here! He will be the first
                            monster in the list!
                        </p>
                    ) : null}
                    <p className="my-4 italic text-sm">
                        It is recommended that players have top tier gear, have
                        reincarnated (at max level) at least twice and have gear
                        with sockets and Gems attached. All players are welcome
                        to participate in the raid, regardless of gear or level,
                        but the more prepared the better chances you have.
                    </p>
                </Fragment>
            );
        }
    }

    renderWeeklyFightLocationDetails() {
        const validLocationNames = ["Alchemy Church"];

        if (this.props.location.type_name === null) {
            return;
        }

        if (validLocationNames.includes(this.props.location.type_name)) {
            return (
                <Fragment>
                    <h5 className="text-orange-500 dark:text-orange-400">
                        Corrupted Alchemy Church
                    </h5>
                    <WarningAlert additional_css={"my-4"}>
                        Exploration cannot be used here if you want the below
                        rewards. You must manually fight. Except for currencies.
                        You can explore here to gain the currencies.
                    </WarningAlert>
                    <div className="border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3"></div>
                    <p className="mb-4">
                        Players who fight here have a smaller selection of
                        monsters to choose from. These are harder creatures.
                        Each monster in the list is restricted to once per week.
                    </p>
                    <p className="mb-4">
                        Players who kill a monster here have a 1% + (max) 15% of
                        their looting skill - 0.02% for each character death, to
                        get a Cosmic item. This item is similar to a Mythic, in
                        the sense that only one can be equipped - but also much
                        more powerful then Mythics.
                    </p>
                    <p className="mb-4">
                        These types of weekly fights reset every Sunday at 3 AM
                        America/Edmonton time.
                    </p>
                    <div className="border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3"></div>
                </Fragment>
            );
        }
    }

    renderInfoForSpecialMaps(): ReactNode {
        if (
            this.props.location.game_map_name === "Delusional Memories" ||
            this.props.location.game_map_name === "The Ice Plane"
        ) {
            return (
                <InfoAlert additional_css={"my-2"}>
                    If you do not have access to Purgatory, than the enemy
                    strength boost of this location does not apply to you. You
                    encounter regular creatures. The rest of the locations
                    effects, such as drop rates and manual fighting still apply.
                </InfoAlert>
            );
        }
    }

    render() {
        return (
            <Fragment>
                <p className="my-3">{this.props.location.description}</p>
                {this.renderRaidDetails()}
                {this.isSpecialLocation() ? (
                    <div className="max-h-[350px] lg:max-h-auto overflow-y-auto">
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                        {this.renderInfoForSpecialMaps()}
                        <div className="flex items-center mb-4">
                            <h4>Special Location Details</h4>
                            <div>
                                <button
                                    type={"button"}
                                    onClick={this.manageHelpDialogue.bind(this)}
                                    className="text-blue-500 dark:text-blue-300 ml-2"
                                >
                                    <i className={"fas fa-info-circle"}></i>{" "}
                                    Help
                                </button>
                            </div>
                        </div>
                        <p className={"mb-4"}>
                            Places like this can increase the enemies stats and
                            resistances as well as skills. It is essential that
                            players craft appropriate resistance and stat
                            reduction gear to survive harder creatures here.
                        </p>
                        <dl className={"mb-4"}>
                            <dt>Increase Core Stats By:</dt>
                            <dd>
                                {formatNumber(
                                    this.props.location
                                        .increases_enemy_stats_by,
                                )}
                            </dd>
                            <dt>Increase Percentage Based Values By:</dt>
                            <dd>
                                {this.props.location
                                    .increase_enemy_percentage_by !== null
                                    ? (
                                          this.props.location
                                              .increase_enemy_percentage_by *
                                          100
                                      ).toFixed(0)
                                    : 0}
                                %
                            </dd>
                            <dt>Drop Chance</dt>
                            <dd>
                                1/100 chance for quest items with a cap of 45%
                                of your looting skill. (If your looting skill
                                bonus is 45% or higher we only use 45%)
                            </dd>
                        </dl>

                        {this.props.location.type_name !== null
                            ? this.renderSpecialType()
                            : null}
                    </div>
                ) : (
                    this.renderWeeklyFightLocationDetails()
                )}

                {this.props.location.quest_reward_item_id !== null ? (
                    <Fragment>
                        <dl className="mb-4">
                            <dt>Quest Item (Gained on visiting)</dt>
                            <dd>
                                <a
                                    href={
                                        "/information/item/" +
                                        this.props.location.quest_reward_item_id
                                    }
                                    target="_blank"
                                >
                                    {
                                        this.props.location.quest_reward_item
                                            .affix_name
                                    }{" "}
                                    <i className="fas fa-external-link-alt"></i>
                                </a>
                            </dd>
                        </dl>
                    </Fragment>
                ) : null}

                {this.props.location.required_quest_item_id !== null ? (
                    <Fragment>
                        <WarningAlert>
                            You cannot simply enter this location with out
                            having the item below.
                        </WarningAlert>
                        <dl className="my-4">
                            <dt>Quest Item Required To Enter</dt>
                            <dd>
                                <a
                                    href={
                                        "/information/item/" +
                                        this.props.location
                                            .required_quest_item_id
                                    }
                                    target="_blank"
                                >
                                    {
                                        this.props.location
                                            .required_quest_item_name
                                    }{" "}
                                    <i className="fas fa-external-link-alt"></i>
                                </a>
                            </dd>
                        </dl>
                    </Fragment>
                ) : null}

                {this.state.open_help_dialogue ? (
                    <SpecialLocationHelpModal
                        manage_modal={this.manageHelpDialogue.bind(this)}
                    />
                ) : null}
            </Fragment>
        );
    }
}
