import { parseInt } from "lodash";
import React, { Fragment, ReactNode } from "react";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import PrimaryOutlineButton from "../../../components/ui/buttons/primary-outline-button";
import BasicCard from "../../../components/ui/cards/basic-card";
import { formatNumber } from "../../../lib/game/format-number";
import UnitDetails from "../../../lib/game/kingdoms/deffinitions/unit-details";
import UnitInformationProps from "../../../lib/game/kingdoms/types/unit-information-props";
import BuildingDetails from "../buildings/deffinitions/building-details";
import TimeHelpModal from "../modals/time-help-modal";
import RecruitWithResources from "./recruit-with-resources";

export default class UnitInformation extends React.Component<
    UnitInformationProps,
    any
> {
    constructor(props: UnitInformationProps) {
        super(props);

        this.state = {
            upgrade_section: null,
            success_message: null,
            error_message: null,
            amount_to_recruit: "",
            loading: false,
            show_time_help: false,
            cost_in_gold: 0,
            time_needed: 0,
        };
    }

    calculateCostsForUnit(baseCost: number, amount: number, is_iron: boolean) {
        if (typeof this.props.unit_cost_reduction === "undefined") {
            console.error("unit_cost_reduction is undefined (prop)");

            return "ERROR";
        }

        let cost = baseCost * amount;

        if (is_iron && cost > 1) {
            return cost - cost * this.props.kingdom_iron_cost_reduction;
        }

        return cost;
    }

    setResourceAmount(amount: number, timeNeeded: number) {
        this.setState({
            amount_to_recruit: amount,
            time_needed: parseInt(timeNeeded.toFixed(0)) || 0,
        });
    }

    getAmount() {
        return parseInt(this.state.amount_to_recruit) || 1;
    }

    showSelectedForm(type: string) {
        this.setState({
            upgrade_section: type,
        });
    }

    removeSelection() {
        this.setState({
            upgrade_section: null,
            success_message: null,
            error_message: null,
            amount_to_recruit: "",
            loading: false,
            cost_in_gold: 0,
            time_needed: 0,
        });
    }

    manageHelpDialogue(timeNeeded?: number) {
        this.setState({
            show_time_help: !this.state.show_time_help,
            time_needed:
                typeof timeNeeded !== "undefined"
                    ? timeNeeded
                    : this.state.time_needed,
        });
    }

    cannotBeRecruited(unit: UnitDetails) {
        const building = this.props.buildings.filter(
            (building: BuildingDetails) => {
                return (
                    building.game_building_id ===
                    unit.recruited_from.game_building_id
                );
            }
        );

        if (building.length === 0) {
            return false;
        }

        const foundBuilding: BuildingDetails = building[0];

        return (
            foundBuilding.level < unit.required_building_level ||
            foundBuilding.is_locked
        );
    }

    renderSelectedSection(): ReactNode {

        if (this.state.upgrade_section === 'resources') {
            return <RecruitWithResources
                kingdom_id={this.props.kingdom_id}
                character_id={this.props.character_id}
                unit={this.props.unit}
                unit_cost_reduction={this.props.unit_cost_reduction}
                kingdom_unit_time_reduction={
                    this.props.kingdom_unit_time_reduction
                }
                manage_help_dialogue={this.manageHelpDialogue.bind(
                    this
                )}
                remove_selection={this.removeSelection.bind(this)}
                set_resource_amount={this.setResourceAmount.bind(this)}
            />
        }

        return null;
    }


    render() {
        return (
            <Fragment>
                <BasicCard>
                    <div className="text-right cursor-pointer text-red-500">
                        <button onClick={() => this.props.close()}>
                            <i className="fas fa-minus-circle"></i>
                        </button>
                    </div>
                    {this.cannotBeRecruited(this.props.unit) ? (
                        <div className="mt-4 mb-4">
                            <DangerAlert>
                                You must Train:{" "}
                                {this.props.unit.recruited_from.building_name}{" "}
                                to level:{" "}
                                {this.props.unit.required_building_level} before
                                you can recruit these units. Check the buildings
                                tab. If the building is red, you must unlock the
                                building before leveling it.
                            </DangerAlert>
                        </div>
                    ) : null}
                    <div className={"grid md:grid-cols-2 gap-4 mb-4 mt-4"}>
                        <div>
                            <h3>Basic Info</h3>
                            <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6"></div>
                            <dl>
                                <dt>Name</dt>
                                <dd>{this.props.unit.name}</dd>
                                <dt>Attack</dt>
                                <dd>{this.props.unit.attack}</dd>
                                <dt>Defence</dt>
                                <dd>{this.props.unit.defence}</dd>
                                <dt>Heal % (For one unit. Stacks.)</dt>
                                <dd>
                                    {this.props.unit.heal_percentage !== null
                                        ? (
                                              this.props.unit.heal_percentage *
                                              100
                                          ).toFixed(0)
                                        : 0}
                                    %
                                </dd>
                                <dt>Good for attacking?</dt>
                                <dd>
                                    {this.props.unit.attacker ? "Yes" : "No"}
                                </dd>
                                <dt>Good for defending?</dt>
                                <dd>
                                    {this.props.unit.defender ? "Yes" : "No"}
                                </dd>
                            </dl>
                        </div>
                        <div className="border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6"></div>
                        <div>
                            <h3>Upgrade Costs (For 1 Unit)</h3>
                            <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6"></div>
                            <dl className="mb-5">
                                <dt>Stone Cost:</dt>
                                <dd>
                                    {formatNumber(
                                        this.calculateCostsForUnit(
                                            this.props.unit.stone_cost,
                                            this.getAmount(),
                                            false
                                        )
                                    )}
                                </dd>
                                <dt>Clay Cost:</dt>
                                <dd>
                                    {formatNumber(
                                        this.calculateCostsForUnit(
                                            this.props.unit.clay_cost,
                                            this.getAmount(),
                                            false
                                        )
                                    )}
                                </dd>
                                <dt>Wood Cost:</dt>
                                <dd>
                                    {formatNumber(
                                        this.calculateCostsForUnit(
                                            this.props.unit.wood_cost,
                                            this.getAmount(),
                                            false
                                        )
                                    )}
                                </dd>
                                <dt>Iron Cost:</dt>
                                <dd>
                                    {formatNumber(
                                        this.calculateCostsForUnit(
                                            this.props.unit.iron_cost,
                                            this.getAmount(),
                                            true
                                        )
                                    )}
                                </dd>
                                <dt>Steel Cost:</dt>
                                <dd>
                                    {formatNumber(
                                        this.calculateCostsForUnit(
                                            this.props.unit.steel_cost,
                                            this.getAmount(),
                                            false
                                        )
                                    )}
                                </dd>
                                <dt>Population Cost:</dt>
                                <dd>
                                    {formatNumber(
                                        this.calculateCostsForUnit(
                                            this.props.unit.required_population,
                                            this.getAmount(),
                                            false
                                        )
                                    )}
                                </dd>
                                <dt>Base Time For One (Seconds):</dt>
                                <dd>
                                    {formatNumber(
                                        this.props.unit.time_to_recruit
                                    )}
                                </dd>
                                {
                                    this.state.upgrade_section === "resources" ?
                                        <Fragment>
                                            <dt>Time Required (Seconds):</dt>
                                            <dd className="flex items-center">
                                                <span>
                                                    {formatNumber(
                                                        this.state.time_needed
                                                    )}
                                                </span>
                                                <div>
                                                    <div className="ml-2">
                                                        <button
                                                            type={"button"}
                                                            onClick={() =>
                                                                this.manageHelpDialogue()
                                                            }
                                                            className="text-blue-500 dark:text-blue-300"
                                                        >
                                                            <i
                                                                className={
                                                                    "fas fa-info-circle"
                                                                }
                                                            ></i>{" "}
                                                            Help
                                                        </button>
                                                    </div>
                                                </div>
                                            </dd>
                                        </Fragment>
                                    : null
                                }
                            </dl>
                            {this.cannotBeRecruited(
                                this.props.unit
                            ) ? null : this.props.is_in_queue ? (
                                <p className="mb-5 mt-5">
                                    You must wait for the units recruitment to
                                    end.
                                </p>
                            ) : this.props.kingdom_current_population === 0 ? (
                                <p className="mb-5 mt-5">
                                    You have no population to recruit units
                                    with.
                                </p>
                            ) : this.state.upgrade_section !== null ? (
                                this.renderSelectedSection()
                            ) : (
                                <Fragment>
                                    <PrimaryOutlineButton button_label={'Recruit Units'} on_click={() => this.showSelectedForm('resources')} />
                                </Fragment>
                            )}
                        </div>
                    </div>
                </BasicCard>
                {this.state.show_time_help ? (
                    <TimeHelpModal
                        is_in_minutes={false}
                        is_in_seconds={true}
                        manage_modal={this.manageHelpDialogue.bind(this)}
                        time={this.state.time_needed}
                    />
                ) : null}
            </Fragment>
        );
    }
}
