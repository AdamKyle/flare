import React from "react";
import LoadingProgressBar from "../ui/progress-bars/loading-progress-bar";
import { serviceContainer } from "../../lib/containers/core-container";
import KingdomResourceTransferAjax from "./ajax/kingdom-resource-transfer-ajax";
import { formatNumber } from "../../lib/game/format-number";
import PrimaryButton from "../ui/buttons/primary-button";
import DropDown from "../ui/drop-down/drop-down";
import DangerButton from "../ui/buttons/danger-button";

export default class KingdomResourceTransfer extends React.Component<any, any> {
    private kingdomResourceTransferRequestAjax: KingdomResourceTransferAjax;

    constructor(props: any) {
        super(props);

        this.state = {
            error_message: null,
            loading: true,
            kingdoms: [],
            index_to_view: 0,
            can_go_back: false,
            can_fo_forward: true,
        };

        this.kingdomResourceTransferRequestAjax = serviceContainer().fetch(
            KingdomResourceTransferAjax,
        );
    }

    componentDidMount() {
        this.kingdomResourceTransferRequestAjax.fetchKingdomsToTransferFrom(
            this,
            this.props.character_id,
            this.props.kingdom_id,
        );
    }

    renderKingdomDetailsForIndex(index: number) {
        const kingdom = this.state.kingdoms[index];

        return (
            <div className="grid md:grid-cols-2 gap-2">
                <dl>
                    <dt>Name</dt>
                    <dd>{kingdom.kingdom_name}</dd>
                    <dt>X/Y</dt>
                    <dd>
                        {kingdom.x_position}/{kingdom.y_position}
                    </dd>
                    <dt>Time to travel</dt>
                    <dd>{kingdom.time_to_travel} Minutes</dd>
                </dl>
                <div className="border-b-2 block lg:hidden border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                <dl>
                    <dt>Current Wood</dt>
                    <dd>{formatNumber(kingdom.current_wood)}</dd>
                    <dt>Current Clay</dt>
                    <dd>{formatNumber(kingdom.current_clay)}</dd>
                    <dt>Current Stone</dt>
                    <dd>{formatNumber(kingdom.current_stone)}</dd>
                    <dt>Current Iron</dt>
                    <dd>{formatNumber(kingdom.current_iron)}</dd>
                    <dt>Current Steel</dt>
                    <dd>{formatNumber(kingdom.current_steel)}</dd>
                </dl>
            </div>
        );
    }

    render() {
        if (this.state.loading) {
            return <LoadingProgressBar />;
        }

        return (
            <div>
                <h3>Kingdom Resource Request</h3>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4"></div>
                <p>
                    You can only request resources from one kingdom at a time.
                    Below is a list of kingdoms that have resources you can
                    request from. By default players can request a max of 5,000
                    resources of any or all types. If the kingdom you request
                    from, has Airships, you can use one and increase the max to
                    10,000 at a time. There are passive skills that allow you to
                    increase this amount when using an Airship.
                </p>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                <div className="max-w-full md:max-w-[75%] md:mr-auto md:ml-auto">
                    {this.state.kingdoms.length > 0 ? (
                        <div>
                            {this.renderKingdomDetailsForIndex(
                                this.state.index_to_view,
                            )}
                            <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                            <div className="flex items-center mb-5">
                                <label className="w-1/3">
                                    Amount to transfer
                                </label>
                                <div className="w-1/3">
                                    <input
                                        type="number"
                                        value={0}
                                        onChange={() => {}}
                                        className="form-control"
                                        disabled={this.state.loading}
                                    />
                                </div>
                                <div className="w-1/3">
                                    <div className="ml-2">
                                        <DropDown
                                            menu_items={[
                                                {
                                                    name: "Wood",
                                                    icon_class:
                                                        "fas fa-shopping-bag",
                                                    on_click: () => () => {},
                                                },
                                                {
                                                    name: "Clay",
                                                    icon_class:
                                                        "ra ra-bubbling-potion",
                                                    on_click: () => () => {},
                                                },
                                                {
                                                    name: "Stone",
                                                    icon_class: "fas fa-gem",
                                                    on_click: () => () => {},
                                                },
                                                {
                                                    name: "Iron",
                                                    icon_class: "fas fa-gem",
                                                    on_click: () => () => {},
                                                },
                                                {
                                                    name: "Steel",
                                                    icon_class: "fas fa-gem",
                                                    on_click: () => () => {},
                                                },
                                                {
                                                    name: "All",
                                                    icon_class: "fas fa-gem",
                                                    on_click: () => () => {},
                                                },
                                            ]}
                                            button_title={"Selected: Wood"}
                                            selected_name={""}
                                            disabled={false}
                                        />
                                    </div>
                                </div>
                            </div>
                            <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                            <div className="flex items-center mb-5">
                                <div className="w-1/3">
                                    <label>Use Air Ships?</label>
                                </div>
                                <div>
                                    <input
                                        type="checkbox"
                                        onChange={() => {}}
                                        className="form-checkbox"
                                        disabled={this.state.loading}
                                    />
                                </div>
                            </div>
                            <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                            <h5>Resources to transfer</h5>
                            <dl>
                                <dt>Amount:</dt>
                                <dd className="text-green-700 dark:text-green-500">
                                    +9999
                                </dd>
                                <dt>For Type:</dt>
                                <dd className="text-orange-700 dark:text-orange-500">
                                    All
                                </dd>
                                <dt>Use Air Ship</dt>
                                <dd>Yes</dd>
                                <dt>Population Cost</dt>
                                <dd>50</dd>
                            </dl>
                            <DangerButton
                                button_label={"Clear"}
                                on_click={() => {}}
                                additional_css={"my-3"}
                            />
                            <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
                            <div className="my-4 flex justify-between">
                                <PrimaryButton
                                    button_label={"Previous"}
                                    on_click={() => {}}
                                    disabled={!this.state.can_go_back}
                                />
                                <PrimaryButton
                                    button_label={"Next"}
                                    on_click={() => {}}
                                    disabled={!this.state.can_fo_forward}
                                />
                            </div>
                        </div>
                    ) : (
                        <p>
                            There are no kingdoms to request resources from.
                            Settle some more.
                        </p>
                    )}
                </div>
            </div>
        );
    }
}
