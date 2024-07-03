import React, { ReactNode } from "react";
import Select from "react-select";
import SuccessOutlineButton from "../../ui/buttons/success-outline-button";
import { UnitTypes } from "../deffinitions/unit-types";
import DangerAlert from "../../ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import FetchKingdomsForSelectionAjax from "../ajax/fetch-kingdoms-for-selection-ajax";
import { serviceContainer } from "../../../lib/containers/core-container";
import PrimaryOutlineButton from "../../ui/buttons/primary-outline-button";
import DangerOutlineButton from "../../ui/buttons/danger-outline-button";
import SendUnitRecruitmentRequestModal from "./modals/send-unit-recruitment-request-modal";
import UnitQueuesTable from "./unit-queues-table";
import OrangeOutlineButton from "../../ui/buttons/orange-outline-button";

export default class UnitRecruitment extends React.Component<any, any> {
    private fetchKingdomsForSelection: FetchKingdomsForSelectionAjax;

    constructor(props: any) {
        super(props);

        this.state = {
            processing_request: false,
            loading: true,
            show_unit_recruitment_confirmation: false,
            show_unit_queue_table: false,
            error_message: null,
            success_message: null,
            unit_recruitment_data: [],
            kingdoms_for_selection: [],
            unit_queues: [],
        };

        this.fetchKingdomsForSelection = serviceContainer().fetch(
            FetchKingdomsForSelectionAjax,
        );
    }

    componentDidMount() {
        this.fetchKingdomsForSelection.fetchDetails(
            this,
            this.props.kingdom.character_id,
            this.props.kingdom.id,
        );
    }

    manageUnitRecruitment() {
        this.setState({
            show_unit_recruitment_confirmation:
                !this.state.show_unit_recruitment_confirmation,
        });
    }

    kingdomSelectOptions() {
        return this.state.kingdoms_for_selection.map(
            (kingdom: { name: string; id: number }) => {
                return {
                    value: kingdom.id,
                    label: kingdom.name,
                };
            },
        );
    }

    updateUnitRecruitmentOrders = (
        event: React.ChangeEvent<HTMLInputElement>,
        unitName: string,
    ) => {
        let value = Math.min(parseInt(event.target.value, 10) || 0, 1000000);

        if (value < 0) {
            return;
        }

        this.setState((prevState: any) => {
            let updatedData = prevState.unit_recruitment_data.map(
                (unit: { name: string; kingdom_ids: number[] }) => {
                    if (unit.name === unitName) {
                        return { ...unit, amount: value };
                    }
                    return unit;
                },
            );

            if (
                !updatedData.some(
                    (unit: { name: string }) => unit.name === unitName,
                )
            ) {
                updatedData.push({
                    name: unitName,
                    amount: value,
                    kingdom_ids: [],
                });
            }

            if (value === 0) {
                updatedData = updatedData.filter(
                    (unit: { name: string }) => unit.name !== unitName,
                );
            }

            return { unit_recruitment_data: updatedData };
        });
    };

    updateUnitKingdomSelection = (unitName: string, selectedOptions: any) => {
        const selectedKingdomIds = selectedOptions.map(
            (option: { value: number }) => option.value,
        );
        this.setState((prevState: any) => {
            let updatedData = prevState.unit_recruitment_data.map(
                (unit: { name: string }) => {
                    if (unit.name === unitName) {
                        return { ...unit, kingdom_ids: selectedKingdomIds };
                    }
                    return unit;
                },
            );

            return { unit_recruitment_data: updatedData };
        });
    };

    selectAllKingdoms = (unitName: string) => {
        const allKingdomIds = this.state.kingdoms_for_selection.map(
            (kingdom: { id: number }) => kingdom.id,
        );
        this.setState((prevState: any) => {
            let updatedData = prevState.unit_recruitment_data.map(
                (unit: { name: string }) => {
                    if (unit.name === unitName) {
                        return { ...unit, kingdom_ids: allKingdomIds };
                    }
                    return unit;
                },
            );

            return { unit_recruitment_data: updatedData };
        });
    };

    resetKingdomSelection = (unitName: string) => {
        this.setState((prevState: any) => {
            let updatedData = prevState.unit_recruitment_data.map(
                (unit: { name: string }) => {
                    if (unit.name === unitName) {
                        return { ...unit, kingdom_ids: [] };
                    }
                    return unit;
                },
            );

            return { unit_recruitment_data: updatedData };
        });
    };

    renderUnitRecruitmentSection(unitName: string): ReactNode {
        const unitData = this.state.unit_recruitment_data.find(
            (unit: { name: string }) => unit.name === unitName,
        );
        const inputValue = unitData ? unitData.amount : "";
        const selectedKingdoms = unitData
            ? this.state.kingdoms_for_selection
                  .filter((kingdom: { id: number }) =>
                      unitData.kingdom_ids.includes(kingdom.id),
                  )
                  .map((kingdom: { id: number; name: string }) => ({
                      value: kingdom.id,
                      label: kingdom.name,
                  }))
            : [];

        const allKingdomsSelected =
            selectedKingdoms.length ===
            this.state.kingdoms_for_selection.length;

        return (
            <div className="flex flex-col md:flex-row items-center space-y-2 md:space-y-0 md:space-x-4 my-4">
                <label className="block text-gray-700 w-32 md:w-40">
                    {unitName}
                </label>
                <input
                    type="number"
                    value={inputValue}
                    className="block border rounded p-2 w-full md:w-40"
                    onChange={(event: React.ChangeEvent<HTMLInputElement>) => {
                        this.updateUnitRecruitmentOrders(event, unitName);
                    }}
                />
                {allKingdomsSelected ? (
                    <DangerOutlineButton
                        button_label={"Reset Kingdom Selection"}
                        on_click={() => this.resetKingdomSelection(unitName)}
                        additional_css={"w-full md:w-auto"}
                    />
                ) : (
                    <>
                        <div className="w-full md:w-80">
                            <Select
                                options={this.kingdomSelectOptions()}
                                placeholder="Please select"
                                className="block w-full"
                                isMulti={true}
                                value={selectedKingdoms}
                                onChange={(selectedOptions) =>
                                    this.updateUnitKingdomSelection(
                                        unitName,
                                        selectedOptions,
                                    )
                                }
                            />
                        </div>
                        <span className="text-xl md:text-base">Or</span>
                        <PrimaryOutlineButton
                            button_label={"Select all Kingdoms"}
                            on_click={() => this.selectAllKingdoms(unitName)}
                            disabled={inputValue === ""}
                        />
                    </>
                )}
            </div>
        );
    }

    manageUnitQueueTable() {
        this.setState({
            show_unit_queue_table: !this.state.show_unit_queue_table,
        });
    }

    renderUnitSections(): ReactNode[] {
        return Object.values(UnitTypes).map((unit) =>
            this.renderUnitRecruitmentSection(unit),
        );
    }

    isSendButtonDisabled() {
        const { unit_recruitment_data } = this.state;
        return (
            unit_recruitment_data.length === 0 ||
            unit_recruitment_data.some(
                (unit: { kingdom_ids: number[] }) =>
                    unit.kingdom_ids.length === 0,
            )
        );
    }

    renderRecruitmentAndQueueTabs() {
        return (
            <div>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>
                <div className="flex items-center relative">
                    <h3>Capital City Unit Request Queue</h3>
                    <SuccessOutlineButton
                        button_label={"Back to manage units"}
                        on_click={this.manageUnitQueueTable.bind(this)}
                        additional_css={"absolute right-0"}
                    />
                </div>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>
                <UnitQueuesTable
                    unit_queues={this.state.unit_queues}
                    kingdom_id={this.props.kingdom.id}
                    character_id={this.props.kingdom.character_id}
                    user_id={this.props.user_id}
                />
            </div>
        );
    }

    renderRecruitmentSection() {
        return (
            <div>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>
                <div className="flex items-center relative">
                    <h3>Oversee your kingdoms units</h3>
                    <SuccessOutlineButton
                        button_label={"Back to council"}
                        on_click={this.props.manage_unit_section}
                        additional_css={"absolute right-0"}
                    />
                </div>
                {this.state.processing_request ? <LoadingProgressBar /> : null}
                {this.state.error_message !== null ? (
                    <DangerAlert additional_css={"my-2"}>
                        {this.state.error_message}
                    </DangerAlert>
                ) : null}
                {this.state.success_message !== null ? (
                    <DangerAlert additional_css={"my-2"}>
                        {this.state.success_message}
                    </DangerAlert>
                ) : null}
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>
                <p className="my-2">
                    Below, for each unit type, you may enter a number between 1
                    and 1,000,000. It is vital you train{" "}
                    <a
                        href="/information/kingdom-passive-skills"
                        target="_blank"
                    >
                        Kingdom Passives{" "}
                        <i className="fas fa-external-link-alt"></i>
                    </a>{" "}
                    such as: <strong>Unit Management</strong>, and your own{" "}
                    <a href="/information/skill-information" target="_blank">
                        Character Skill{" "}
                        <i className="fas fa-external-link-alt"></i>
                    </a>
                    : <strong>Kinggmanship</strong> which helps reduce the time
                    it takes to recruit large amounts of units.
                </p>
                <p className="my-2">
                    Should you not have the people to purchase that amount you
                    enter, we will buy people for you - from your own gold.
                    Should you not have the resources to request the amount, we
                    will send out resource requests for you.
                </p>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>

                <div className="flex space-x-2 items-center">
                    <PrimaryOutlineButton
                        button_label={"Send Recruitment Orders"}
                        on_click={this.manageUnitRecruitment.bind(this)}
                        additional_css={"py-2 px-3 flex-shrink-0"}
                        disabled={this.isSendButtonDisabled()}
                    />
                    <OrangeOutlineButton
                        button_label={"View Queue"}
                        on_click={this.manageUnitQueueTable.bind(this)}
                        additional_css={"py-2 px-3 flex-shrink-0"}
                    />
                    <DangerOutlineButton
                        button_label={"Reset Form"}
                        on_click={() =>
                            this.setState({ unit_recruitment_data: [] })
                        }
                        additional_css={"py-2 px-3 flex-shrink-0"}
                    />
                </div>

                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>
                {this.state.kingdoms_for_selection.length <= 0 ? (
                    <p className="text-center italic">
                        All your kingdoms seem to be busy in this regard. Check:
                        View Queues to see whats going on.
                    </p>
                ) : (
                    <>
                        <p className="text-blue-700 dark:text-blue-500">
                            You must select at least one kingdom for each type
                            of unit you want to request.
                        </p>
                        <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>
                        {this.renderUnitSections()}
                    </>
                )}
                {this.state.show_unit_recruitment_confirmation ? (
                    <SendUnitRecruitmentRequestModal
                        is_open={this.state.show_unit_recruitment_confirmation}
                        manage_modal={this.manageUnitRecruitment.bind(this)}
                        character_id={this.props.kingdom.character_id}
                        kingdom_id={this.props.kingdom.id}
                        params={this.state.unit_recruitment_data}
                        reset_request_form={() => {
                            this.setState({ unit_recruitment_data: [] });
                        }}
                    />
                ) : null}
            </div>
        );
    }

    render() {
        if (this.state.loading) {
            return <LoadingProgressBar />;
        }

        if (this.state.show_unit_queue_table) {
            return this.renderRecruitmentAndQueueTabs();
        }

        return this.renderRecruitmentSection();
    }
}
