import React, { Fragment } from "react";
import BasicCard from "../../components/ui/cards/basic-card";
import CharacterTabs from "./components/character-tabs";
import CharacterSkillsTabs from "./components/character-skills-tabs";
import CharacterInventoryTabs from "./components/character-inventory-tabs";
import CharacterSheetProps from "../../lib/game/character-sheet/types/character-sheet-props";
import DangerAlert from "../../components/ui/alerts/simple-alerts/danger-alert";
import WarningAlert from "../../components/ui/alerts/simple-alerts/warning-alert";
import Select from "react-select";
import PrimaryButton from "../../components/ui/buttons/primary-button";
import LoadingProgressBar from "../../components/ui/progress-bars/loading-progress-bar";
import { AxiosError, AxiosResponse } from "axios";
import Ajax from "../../lib/ajax/ajax";
import ReincarnationCheckModal from "./components/modals/reincarnation-check-modal";
import AdditionalStatSection from "../../components/character-sheet/additional-stats-section/additional-stat-section";
import DangerButton from "../../components/ui/buttons/danger-button";

export default class CharacterSheet extends React.Component<
    CharacterSheetProps,
    any
> {
    constructor(props: CharacterSheetProps) {
        super(props);

        this.state = {
            show_inventory_section: false,
            show_skills_section: false,
            show_top_section: false,
            show_additional_character_data: false,
            reincarnating: false,
            success_message: null,
            error_message: null,
            reincarnation_check: false,
        };
    }

    manageReincarnationCheck() {
        this.setState({
            reincarnation_check: !this.state.reincarnation_check,
        });
    }

    showSection() {
        if (typeof this.props.view_port === "undefined") {
            return true;
        }

        return this.props.view_port > 1600;
    }

    showCloseButton() {
        if (typeof this.props.view_port === "undefined") {
            return false;
        }

        return this.props.view_port < 1600;
    }

    showTopSection() {
        this.setState({
            show_top_section: !this.state.show_top_section,
        });
    }

    showSelectedSection(data: any) {
        switch (data.value) {
            case "inventory":
                return this.manageInventoryManagement();
            case "skills":
                return this.manageSkillsManagement();
            default:
                return;
        }
    }

    showAdditionalCharacterData() {
        this.setState({
            show_additional_character_data:
                !this.state.show_additional_character_data,
        });
    }

    manageInventoryManagement() {
        this.setState({
            show_inventory_section: !this.state.show_inventory_section,
        });
    }

    manageSkillsManagement() {
        this.setState({
            show_skills_section: !this.state.show_skills_section,
        });
    }

    reincarnateCharacter() {
        this.setState(
            {
                reincarnating: true,
                reincarnation_check: false,
            },
            () => {
                new Ajax()
                    .setRoute(
                        "character/reincarnate/" + this.props.character?.id,
                    )
                    .doAjaxCall(
                        "post",
                        (response: AxiosResponse) => {
                            this.setState({
                                reincarnating: false,
                                success_message: response.data.message,
                            });
                        },
                        (error: AxiosError) => {
                            this.setState(
                                {
                                    reincarnating: false,
                                },
                                () => {
                                    if (typeof error.response !== "undefined") {
                                        const response: AxiosResponse = error.response;

                                        this.setState({
                                            error_message:
                                                response.data.message,
                                        });
                                    }
                                },
                            );
                        },
                    );
            },
        );
    }

    render() {
        if (this.props.character === null) {
            return null;
        }

        if (this.state.show_additional_character_data) {
            return (
                <div>
                    <div className={"max-w-[25%] my-4"}>
                        <DangerButton
                            button_label={"Close"}
                            on_click={this.showAdditionalCharacterData.bind(
                                this,
                            )}
                        />
                    </div>

                    <AdditionalStatSection character={this.props.character} />
                </div>
            );
        }

        return (
            <div>
                {this.props.character.is_dead ? (
                    <DangerAlert additional_css={"mb-4"}>
                        <p className="p-3">
                            Christ child! You are dead. Dead people cannot do a
                            lot of things including: Manage inventory, Manage
                            Skills - including passives, Manage Boons or even
                            use items. And they cannot manage their kingdoms!
                            How sad! Go resurrect child! (head to Game tab and
                            click Revive).
                        </p>
                    </DangerAlert>
                ) : null}

                {this.props.character.is_automation_running ? (
                    <WarningAlert additional_css={"mb-4"}>
                        <p className="p-3">
                            Child! You are busy with Automation. You cannot
                            manage aspects of your inventory or skills such as
                            whats training, passives or equipped items.
                        </p>
                        <p className="p-3">
                            How ever, you can still manage the items you craft -
                            such as sell, disenchant and destroy. You can also
                            move items to sets, but not equip sets.
                        </p>
                        <p className="p-3">
                            Please see{" "}
                            <a href="/information/automation" target="_blank">
                                Automation{" "}
                                <i className="fas fa-external-link-alt"></i>
                            </a>{" "}
                            for more details.
                        </p>
                    </WarningAlert>
                ) : null}

                <div className="flex flex-col lg:flex-row w-full gap-2">
                    {this.showSection() || this.state.show_top_section ? (
                        <Fragment>
                            <BasicCard
                                additionalClasses={"overflow-y-auto lg:w-1/2"}
                            >
                                {this.showCloseButton() ? (
                                    <div className="text-right cursor-pointer text-red-500 relative top-[10px]">
                                        <button
                                            onClick={this.showTopSection.bind(
                                                this,
                                            )}
                                        >
                                            <i className="fas fa-minus-circle"></i>
                                        </button>
                                    </div>
                                ) : null}
                                <CharacterTabs
                                    character={this.props.character}
                                    finished_loading={
                                        this.props.finished_loading
                                    }
                                    view_port={this.props.view_port}
                                    manage_addition_data={this.showAdditionalCharacterData.bind(
                                        this,
                                    )}
                                    update_pledge_tab={
                                        this.props.update_pledge_tab
                                    }
                                    update_faction_action_tasks={
                                        this.props.update_faction_action_tasks
                                    }
                                />
                            </BasicCard>
                            <BasicCard
                                additionalClasses={
                                    "overflow-y-auto lg:w-1/2 md:max-h-[325px]"
                                }
                            >
                                <div className="grid lg:grid-cols-2 gap-2">
                                    <div>
                                        <dl>
                                            <dt>Gold:</dt>
                                            <dd>{this.props.character.gold}</dd>
                                            <dt>Gold Dust:</dt>
                                            <dd>
                                                {this.props.character.gold_dust}
                                            </dd>
                                            <dt>Shards:</dt>
                                            <dd>
                                                {this.props.character.shards}
                                            </dd>
                                            <dt>Copper Coins:</dt>
                                            <dd>
                                                {
                                                    this.props.character
                                                        .copper_coins
                                                }
                                            </dd>
                                        </dl>
                                        <div className="mt-6 text-center">
                                            <PrimaryButton
                                                button_label={
                                                    "Reincarnate Character"
                                                }
                                                on_click={this.manageReincarnationCheck.bind(
                                                    this,
                                                )}
                                            />
                                            <p className="text-sm my-2">
                                                <a
                                                    href="/information/reincarnation"
                                                    target="_blank"
                                                >
                                                    What is Reincarnation?{" "}
                                                    <i className="fas fa-external-link-alt"></i>
                                                </a>
                                            </p>
                                            {this.state.reincarnating ? (
                                                <LoadingProgressBar />
                                            ) : null}

                                            {this.state.error_message !==
                                            null ? (
                                                <p className="text-red-500 dark:text-red-400 my-3">
                                                    {this.state.error_message}
                                                </p>
                                            ) : null}

                                            {this.state.success_message !==
                                            null ? (
                                                <p className="text-green-500 dark:text-green-400 my-3">
                                                    {this.state.success_message}
                                                </p>
                                            ) : null}
                                        </div>
                                    </div>
                                    <div className="border-b-2 block lg:hidden border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                                    <div>
                                        <dl>
                                            <dt>Inventory Max:</dt>
                                            <dd>
                                                {
                                                    this.props.character
                                                        .inventory_max
                                                }
                                            </dd>
                                            <dt>Inventory Count:</dt>
                                            <dd>
                                                {
                                                    this.props.character
                                                        .inventory_count
                                                }
                                            </dd>
                                        </dl>
                                        <p className="my-4">
                                            Inventory count consists of both
                                            Usable Items, Items in your
                                            inventory as well as your Gem Bag.
                                            Equipment, Quest items and Sets do
                                            not count towards inventory count.
                                        </p>
                                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                                        <dl>
                                            <dt>Damage Stat:</dt>
                                            <dd>
                                                {
                                                    this.props.character
                                                        .damage_stat
                                                }
                                            </dd>
                                            <dt>To Hit:</dt>
                                            <dd>
                                                {
                                                    this.props.character
                                                        .to_hit_stat
                                                }
                                            </dd>
                                            <dt>Class Bonus:</dt>
                                            <dd>
                                                {(
                                                    this.props.character
                                                        .extra_action_chance
                                                        .chance * 100
                                                ).toFixed(2)}
                                                %
                                            </dd>
                                        </dl>
                                        <p className="mt-4">
                                            Make sure you read up on your{" "}
                                            <a
                                                href={
                                                    "/information/class/" +
                                                    this.props.character
                                                        .class_id
                                                }
                                                target="_blank"
                                            >
                                                class{" "}
                                                <i className="fas fa-external-link-alt"></i>
                                            </a>{" "}
                                            for tips and tricks.
                                        </p>
                                    </div>
                                </div>
                            </BasicCard>
                        </Fragment>
                    ) : (
                        <Fragment>
                            <BasicCard
                                additionalClasses={"overflow-y-auto lg:w-1/2"}
                            >
                                <span className="relative top-[10px]">
                                    <strong>Character Details</strong>
                                </span>
                                <div className="text-right cursor-pointer text-blue-500 relative top-[-12px]">
                                    <button
                                        onClick={this.showTopSection.bind(this)}
                                    >
                                        <i className="fas fa-plus-circle"></i>
                                    </button>
                                </div>
                            </BasicCard>
                        </Fragment>
                    )}
                </div>
                <div className="flex flex-col lg:flex-row gap-2 w-full mt-2">
                    {this.showSection() || this.state.show_skills_section ? (
                        <BasicCard
                            additionalClasses={
                                "overflow-y-auto lg:w-1/2 lg:h-fit"
                            }
                        >
                            {this.showCloseButton() ? (
                                <div className="text-right cursor-pointer text-red-500 relative top-[10px]">
                                    <button
                                        onClick={this.manageSkillsManagement.bind(
                                            this,
                                        )}
                                    >
                                        <i className="fas fa-minus-circle"></i>
                                    </button>
                                </div>
                            ) : null}
                            <CharacterSkillsTabs
                                character_id={this.props.character.id}
                                user_id={this.props.character.user_id}
                                is_dead={this.props.character.is_dead}
                                is_automation_running={
                                    this.props.character.is_automation_running
                                }
                                finished_loading={this.props.finished_loading}
                            />
                        </BasicCard>
                    ) : null}

                    {this.showSection() || this.state.show_inventory_section ? (
                        <BasicCard
                            additionalClasses={
                                "overflow-y-auto lg:w-1/2 lg:h-fit"
                            }
                        >
                            {this.showCloseButton() ? (
                                <div className="text-right cursor-pointer text-red-500 relative top-[10px]">
                                    <button
                                        onClick={this.manageInventoryManagement.bind(
                                            this,
                                        )}
                                    >
                                        <i className="fas fa-minus-circle"></i>
                                    </button>
                                </div>
                            ) : null}

                            <CharacterInventoryTabs
                                character_id={this.props.character.id}
                                is_dead={this.props.character.is_dead}
                                user_id={this.props.character.user_id}
                                is_automation_running={
                                    this.props.character.is_automation_running
                                }
                                finished_loading={this.props.finished_loading}
                                update_disable_tabs={
                                    this.props.update_disable_tabs
                                }
                                view_port={this.props.view_port}
                            />
                        </BasicCard>
                    ) : null}

                    {!this.showSection() &&
                    !this.state.show_inventory_section &&
                    !this.state.show_skills_section ? (
                        <Select
                            onChange={this.showSelectedSection.bind(this)}
                            options={[
                                {
                                    label: "Inventory Management",
                                    value: "inventory",
                                },
                                {
                                    label: "Skill Management",
                                    value: "skills",
                                },
                            ]}
                            menuPosition={"absolute"}
                            menuPlacement={"bottom"}
                            styles={{
                                menuPortal: (base: any) => ({
                                    ...base,
                                    zIndex: 9999,
                                    color: "#000000",
                                }),
                            }}
                            menuPortalTarget={document.body}
                            value={[{ label: "Please Select", value: "" }]}
                        />
                    ) : null}
                </div>

                {this.state.reincarnation_check ? (
                    <ReincarnationCheckModal
                        manage_modal={this.manageReincarnationCheck.bind(this)}
                        handle_reincarnate={this.reincarnateCharacter.bind(
                            this,
                        )}
                    />
                ) : null}
            </div>
        );
    }
}
