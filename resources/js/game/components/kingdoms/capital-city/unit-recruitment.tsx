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

export default class UnitRecruitment extends React.Component<any, any> {
    private fetchKingdomsForSelection: FetchKingdomsForSelectionAjax;

    constructor(props: any) {
        super(props);

        this.state = {
            processing_request: false,
            loading: true,
            error_message: null,
            success_message: null,
            unit_recruitment_data: [],
            kingdoms_for_selection: [],
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
        const value = Math.min(parseInt(event.target.value, 10) || 0, 1000000);
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

    render() {
        if (this.state.loading) {
            return <LoadingProgressBar />;
        }

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
                    and 1,000,000. It is vital you train Kingdom Passives such
                    as: <strong>Unit Management</strong>, and your own Character
                    Skill: <strong>Kinggmanship</strong> which helps reduce the
                    time it takes to recruit large amounts of units.
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
                        on_click={() =>
                            console.log(this.state.unit_recruitment_data)
                        }
                        additional_css={"py-2 px-3 flex-shrink-0"}
                        disabled={this.isSendButtonDisabled()}
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
                {this.renderUnitSections()}
            </div>
        );
    }
}
