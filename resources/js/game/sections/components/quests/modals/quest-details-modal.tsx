import React, { Fragment } from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import ComponentLoading from "../../../../components/ui/loading/component-loading";
import Ajax from "../../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import Tabs from "../../../../components/ui/tabs/tabs";
import TabPanel from "../../../../components/ui/tabs/tab-panel";
import clsx from "clsx";
import InfoAlert from "../../../../components/ui/alerts/simple-alerts/info-alert";
import CurrencyRequirement from "./components/currency-requirement";
import Reward from "./components/reward";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import SuccessAlert from "../../../../components/ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../../../components/ui/alerts/simple-alerts/danger-alert";
import WarningAlert from "../../../../components/ui/alerts/simple-alerts/warning-alert";

export default class QuestDetailsModal extends React.Component<any, any> {
    private tabs: { name: string; key: string }[];

    constructor(props: any) {
        super(props);

        this.state = {
            quest_details: null,
            loading: true,
            handing_in: false,
            success_message: null,
            error_message: null,
        };

        this.tabs = [
            {
                name: "Npc Details",
                key: "npc-details",
            },
            {
                name: "Required To Complete",
                key: "required-to-complete",
            },
            {
                name: "Quest Reward",
                key: "quest-reward",
            },
        ];
    }

    componentDidMount() {
        if (this.props.quest_id === null) {
            return;
        }

        new Ajax()
            .setRoute(
                "quest/" + this.props.quest_id + "/" + this.props.character_id,
            )
            .doAjaxCall(
                "get",
                (result: AxiosResponse) => {
                    this.setState({
                        quest_details: result.data,
                        loading: false,
                    });
                },
                (error: AxiosError) => {},
            );
    }

    handInQuest() {
        this.setState(
            {
                handing_in: true,
            },
            () => {
                new Ajax()
                    .setRoute(
                        "quest/" +
                            this.props.quest_id +
                            "/hand-in-quest/" +
                            this.props.character_id,
                    )
                    .doAjaxCall(
                        "post",
                        (result: AxiosResponse) => {
                            this.setState({
                                handing_in: false,
                                success_message: result.data.message,
                            });

                            const data = result.data;

                            delete data.message;

                            this.props.update_quests(data);
                        },
                        (error: AxiosError) => {
                            if (typeof error.response !== "undefined") {
                                const response = error.response;

                                const message = response.data.hasOwnProperty(
                                    "message",
                                )
                                    ? response.data.message
                                    : response.data.error;

                                this.setState({
                                    handing_in: false,
                                    error_message: message,
                                });
                            }
                        },
                    );
            },
        );
    }

    buildTitle() {
        if (this.state.quest_details === null) {
            return "Fetching details ...";
        }

        return this.state.quest_details.name;
    }

    getNPCCommands(npc: any) {
        return npc.commands.map((command: any) => command.command).join(", ");
    }

    getRequiredQuestDetails() {
        if (this.state.quest_details !== null) {
            if (this.state.quest_details.parent_chain_quest !== null) {
                const questName =
                    this.state.quest_details.parent_chain_quest.name;
                const npcName =
                    this.state.quest_details.parent_chain_quest.npc.real_name;
                const mapName =
                    this.state.quest_details.parent_chain_quest
                        .belongs_to_map_name;

                return (
                    <span>
                        You must complete another quest first, to start this
                        story line. Complete the quest chain starting with:{" "}
                        <strong>{questName}</strong> For the NPC:{" "}
                        <strong>{npcName}</strong> who resides on:{" "}
                        <strong>{mapName}</strong>.
                    </span>
                );
            }

            if (this.state.quest_details.required_quest !== null) {
                const questName = this.state.quest_details.required_quest.name;
                const npcName =
                    this.state.quest_details.required_quest.npc.real_name;
                const mapName =
                    this.state.quest_details.required_quest.belongs_to_map_name;

                return (
                    <span>
                        You must complete another quest first, to start this
                        story line. Complete: <strong>{questName}</strong> For
                        the NPC: <strong>{npcName}</strong> who resides on:{" "}
                        <strong>{mapName}</strong>.
                    </span>
                );
            }
        }

        return <span>Something went wrong.</span>;
    }

    renderPlaneAccessRequirements(map: { map_required_item: any | null }) {
        if (map.map_required_item !== null) {
            return (
                <Fragment>
                    <dt>Required to access</dt>
                    <dd>{map.map_required_item.name}</dd>

                    {map.map_required_item.required_quest !== null ? (
                        <Fragment>
                            <dt>Which needs you to complete (Quest)</dt>
                            <dd>{map.map_required_item.required_quest.name}</dd>
                            <dt>By Speaking to</dt>
                            <dd>
                                {
                                    map.map_required_item.required_quest.npc
                                        .real_name
                                }
                            </dd>
                            <dt>Who is at (X/Y)</dt>
                            <dd>
                                {
                                    map.map_required_item.required_quest.npc
                                        .x_position
                                }
                                /
                                {
                                    map.map_required_item.required_quest.npc
                                        .y_position
                                }
                            </dd>
                            <dt>On plane</dt>
                            <dd>
                                {
                                    map.map_required_item.required_quest.npc
                                        .game_map.name
                                }
                            </dd>
                            {this.renderPlaneAccessRequirements(
                                map.map_required_item.required_quest.npc
                                    .game_map,
                            )}
                        </Fragment>
                    ) : null}

                    {map.map_required_item.required_monster !== null ? (
                        <Fragment>
                            <dt>Which requires you to fight (first)</dt>
                            <dd>
                                {map.map_required_item.required_monster.name}
                            </dd>
                            <dt>Who resides on plane</dt>
                            <dd>
                                {
                                    map.map_required_item.required_monster
                                        .game_map.name
                                }
                            </dd>
                            {this.renderPlaneAccessRequirements(
                                map.map_required_item.required_monster.game_map,
                            )}
                        </Fragment>
                    ) : null}
                </Fragment>
            );
        }

        return null;
    }

    renderLocations(locations: any) {
        return locations.map((location: any) => {
            return (
                <Fragment>
                    <dl>
                        <dt>By Going to</dt>
                        <dd>{location.name}</dd>
                        <dt>Which is at (X/Y)</dt>
                        <dd>
                            {location.x}/{location.y}
                        </dd>
                        <dt>On Plane</dt>
                        <dd>{location.map.name}</dd>
                        {this.renderPlaneAccessRequirements(location.map)}
                    </dl>
                </Fragment>
            );
        });
    }

    getMonsterTypeForRenderingItem(requiredMonster: any): string {
        let type = "Regular Monster";

        if (requiredMonster.is_celestial_entity) {
            type = "Celestial";
        }

        if (requiredMonster.is_raid_monster) {
            type = "Raid Monster";
        }

        if (requiredMonster.is_raid_boss) {
            type = "Raid Boss";
        }

        return type;
    }

    renderItem(item: any) {
        return (
            <Fragment>
                {item.drop_location_id !== null ? (
                    <div className="mb-4">
                        <InfoAlert>
                            <p className="mb-2">
                                Some items, such as this one, only drop when you
                                are at a special location. These locations
                                increase enemy strength making them more of a
                                challenge.
                            </p>
                            <p className="mb-2">
                                These items have a small chance to drop while
                                your looting skill is capped at 45% here.
                            </p>
                            <p>
                                <strong>
                                    These items will not drop if you are using
                                    Exploration. You must manually farm these
                                    quest items.
                                </strong>
                            </p>
                        </InfoAlert>
                    </div>
                ) : null}
                {item.required_monster !== null ? (
                    item.required_monster.is_celestial_entity ? (
                        <div className="mb-4">
                            <InfoAlert>
                                <p className="mb-2">
                                    Some quests such as this one may have you
                                    fighting a Celestial entity. You can check
                                    the{" "}
                                    <a href="/information/npcs" target="_blank">
                                        help docs (NPC's)
                                    </a>{" "}
                                    to find out, based on which plane, which
                                    Summoning NPC you ned to speak to inorder to
                                    conjure the entity, there is only one per
                                    plane.
                                </p>
                                <p>
                                    Celestial Entities below Dungeons plane,
                                    will not be included in the weekly spawn.
                                </p>
                            </InfoAlert>
                        </div>
                    ) : null
                ) : null}
                <dl>
                    {item.required_monster !== null ? (
                        <Fragment>
                            <dt>Obtained by killing</dt>
                            <dd>
                                {item.required_monster.name}{" "}
                                {"(" +
                                    this.getMonsterTypeForRenderingItem(
                                        item.required_monster,
                                    ) +
                                    ")"}
                            </dd>
                            <dt>Resides on plane</dt>
                            <dd>{item.required_monster.game_map.name}</dd>
                            {this.renderPlaneAccessRequirements(
                                item.required_monster.game_map,
                            )}
                        </Fragment>
                    ) : null}

                    {item.required_quest !== null ? (
                        <Fragment>
                            <dt>Obtained by completing</dt>
                            <dd>{item.required_quest.name}</dd>
                            <dt>Which belongs to (NPC)</dt>
                            <dd>{item.required_quest.npc.real_name}</dd>
                            <dt>Who is on the plane of</dt>
                            <dd>{item.required_quest.npc.game_map.name}</dd>
                            <dt>At coordinates (X/Y)</dt>
                            <dd>
                                {item.required_quest.npc.x_position} /{" "}
                                {item.required_quest.npc.y_position}
                            </dd>
                            {this.renderPlaneAccessRequirements(
                                item.required_quest.npc.game_map,
                            )}
                        </Fragment>
                    ) : null}

                    {item.drop_location_id !== null ? (
                        <Fragment>
                            <dt>
                                By Visiting (Fighting monsters for it to drop)
                            </dt>
                            <dd>{item.drop_location.name}</dd>
                            <dt>At coordinates (X/Y)</dt>
                            <dd>
                                {item.drop_location.x} / {item.drop_location.y}
                            </dd>
                            <dt>Which is on the plane</dt>
                            <dd>{item.drop_location.map.name}</dd>
                            {this.renderPlaneAccessRequirements(
                                item.drop_location.map,
                            )}
                        </Fragment>
                    ) : null}
                </dl>
                {item.locations.length > 0 ? (
                    <Fragment>
                        <hr />
                        <h3 className="tw-font-light">Locations</h3>
                        <p>
                            Locations that will give you the item, just for
                            visiting.
                        </p>
                        <hr />
                        {this.renderLocations(item.locations)}
                    </Fragment>
                ) : null}
            </Fragment>
        );
    }

    fetchNpcPlaneAccess() {
        let npcPlaneAccess = null;

        if (!this.state.loading) {
            npcPlaneAccess = this.renderPlaneAccessRequirements(
                this.state.quest_details.npc.game_map,
            );
        }

        return npcPlaneAccess;
    }

    getText(text: string | null): string {
        if (text === null) {
            return "";
        }

        return text.replace(/\n/g, "<br/>");
    }

    render() {
        const npcPLaneAccess = this.fetchNpcPlaneAccess();

        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.handle_close}
                secondary_actions={{
                    secondary_button_disabled:
                        !this.props.is_parent_complete ||
                        this.props.is_quest_complete,
                    secondary_button_label: "Hand in",
                    handle_action: this.handInQuest.bind(this),
                }}
                title={this.buildTitle()}
                large_modal={false}
            >
                {this.state.loading ? (
                    <div className={"h-24 mt-10 relative"}>
                        <ComponentLoading />
                    </div>
                ) : (
                    <Fragment>
                        {!this.props.is_required_quest_complete ? (
                            <WarningAlert additional_css={"my-4"}>
                                {this.getRequiredQuestDetails()}
                            </WarningAlert>
                        ) : null}
                        <Tabs tabs={this.tabs} full_width={true}>
                            <TabPanel key={"npc-details"}>
                                <div
                                    className={clsx({
                                        "grid md:grid-cols-2 gap-2 max-h-[200px] md:max-h-full overflow-y-auto md:overflow-y-visible":
                                            npcPLaneAccess !== null,
                                    })}
                                >
                                    <div>
                                        <div className="border-b-2 block border-b-gray-300 dark:border-b-gray-600 my-3 md:hidden"></div>
                                        <strong>Basic Info</strong>
                                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                                        <dl>
                                            <dt>Name</dt>
                                            <dd>
                                                {
                                                    this.state.quest_details.npc
                                                        .real_name
                                                }
                                            </dd>
                                            <dt>Coordinates (X/Y)</dt>
                                            <dd>
                                                {
                                                    this.state.quest_details.npc
                                                        .x_position
                                                }{" "}
                                                /{" "}
                                                {
                                                    this.state.quest_details.npc
                                                        .y_position
                                                }
                                            </dd>
                                            <dt>On Plane</dt>
                                            <dd>
                                                <a
                                                    href={
                                                        "/information/map/" +
                                                        this.state.quest_details
                                                            .npc.game_map.id
                                                    }
                                                    target="_blank"
                                                >
                                                    {
                                                        this.state.quest_details
                                                            .npc.game_map.name
                                                    }{" "}
                                                    <i className="fas fa-external-link-alt"></i>
                                                </a>
                                            </dd>
                                            <dt>Must be at same location?</dt>
                                            <dd>
                                                {this.state.quest_details.npc
                                                    .must_be_at_same_location
                                                    ? "Yes"
                                                    : "No"}
                                            </dd>
                                        </dl>
                                    </div>
                                    {npcPLaneAccess !== null ? (
                                        <div
                                            className={clsx({
                                                "md:pl-2":
                                                    npcPLaneAccess !== null,
                                            })}
                                        >
                                            <div className="border-b-2 block border-b-gray-300 dark:border-b-gray-600 my-3 md:hidden"></div>
                                            <strong>
                                                Npc Access Requirements
                                            </strong>
                                            <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                                            <dl
                                                className={
                                                    "md:ml-8 md:max-h-[250px] md:overflow-y-auto"
                                                }
                                            >
                                                {npcPLaneAccess}
                                            </dl>
                                        </div>
                                    ) : null}
                                </div>
                                <div
                                    className={
                                        "my-4 max-h-[160px] overflow-x-auto border border-slate-200 dark:border-slate-700 rounded-md bg-slate-200 dark:bg-slate-700 p-4 " +
                                        (!this.props.is_parent_complete ||
                                        !this.props.is_required_quest_complete
                                            ? " blur-sm"
                                            : "")
                                    }
                                >
                                    {this.props.is_quest_complete ? (
                                        <div
                                            dangerouslySetInnerHTML={{
                                                __html: this.getText(
                                                    this.state.quest_details
                                                        .after_completion_description,
                                                ),
                                            }}
                                        ></div>
                                    ) : (
                                        <div
                                            dangerouslySetInnerHTML={{
                                                __html: this.getText(
                                                    this.state.quest_details
                                                        .before_completion_description,
                                                ),
                                            }}
                                        ></div>
                                    )}
                                </div>
                            </TabPanel>
                            <TabPanel key={"required-to-complete"}>
                                <CurrencyRequirement
                                    quest={this.state.quest_details}
                                    item_requirements={this.renderItem.bind(
                                        this,
                                    )}
                                />
                            </TabPanel>
                            <TabPanel key={"quest-reward"}>
                                <Reward quest={this.state.quest_details} />
                            </TabPanel>
                        </Tabs>

                        {this.state.success_message !== null ? (
                            <div className="mb-4 mt-4">
                                <SuccessAlert>
                                    {this.state.success_message}
                                </SuccessAlert>
                            </div>
                        ) : null}

                        {this.state.error_message !== null ? (
                            <div className="mb-4 mt-4">
                                <DangerAlert>
                                    {this.state.error_message}
                                </DangerAlert>
                            </div>
                        ) : null}

                        {this.state.handing_in ? <LoadingProgressBar /> : null}
                    </Fragment>
                )}
            </Dialogue>
        );
    }
}
