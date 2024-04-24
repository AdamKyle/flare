import React, { Fragment } from "react";
import LoadingProgressBar from "../../../ui/progress-bars/loading-progress-bar";
import Ajax from "../../../../lib/ajax/ajax";
import { AxiosResponse } from "axios";
import Select from "react-select";
import RemoveGemComparison from "../../../../sections/components/gems/remove-gem-comparison";
import RemoveGemsState from "./types/remove-gems-state";

export default class RemoveGem extends React.Component<any, RemoveGemsState> {
    constructor(props: any) {
        super(props);

        this.state = {
            fetching_data: true,
            removing_gem: false,
            selected_item: 0,
            items: [],
            gems: [],
            selected_gem_data: null,
        };
    }

    componentDidMount() {
        new Ajax()
            .setRoute("seer-camp/gems-to-remove/" + this.props.character_id)
            .doAjaxCall("get", (result: AxiosResponse) => {
                this.setState({
                    items: result.data.items,
                    gems: result.data.gems,
                    fetching_data: false,
                });
            });
    }

    selectedItems(data: any) {
        if (data.value <= 0) {
            return;
        }

        const gemData = this.state.gems.find((gem: any) => {
            return gem.slot_id === data.value;
        });

        if (typeof gemData === "undefined") {
            return;
        }

        this.setState({
            selected_item: data.value,
            selected_gem_data: gemData,
        });
    }

    updateRemoveGemState<T>(value: T, property: string) {
        this.setState((prevState: any) => ({
            ...prevState,
            [property]: value,
        }));
    }

    itemsToSelect() {
        const options = this.state.items.map((item: any) => {
            return {
                label: item.name,
                value: item.slot_id,
            };
        });

        options.unshift({
            label: "Please select item",
            value: 0,
        });

        return options;
    }

    selectedItem() {
        if (this.state.selected_item === 0) {
            return {
                label: "Please select item",
                value: 0,
            };
        }

        const item = this.state.items.find(
            (item: any) => item.slot_id === this.state.selected_item,
        );

        if (typeof item === "undefined") {
            return {
                label: "Please select item",
                value: 0,
            };
        }

        return {
            label: item.name,
            value: item.slot_id,
        };
    }

    getItemName(): string | null {
        const item: any = this.state.items.find(
            (item: any) => item.slot_id === this.state.selected_item,
        );

        if (typeof item !== "undefined") {
            return item.name;
        }

        return null;
    }

    render() {
        if (this.state.fetching_data) {
            return <LoadingProgressBar />;
        }

        if (this.state.selected_gem_data === null) {
            return (
                <Select
                    onChange={this.selectedItems.bind(this)}
                    options={this.itemsToSelect()}
                    menuPosition={"absolute"}
                    menuPlacement={"bottom"}
                    styles={{
                        menuPortal: (base) => ({
                            ...base,
                            zIndex: 9999,
                            color: "#000000",
                        }),
                    }}
                    menuPortalTarget={document.body}
                    value={this.selectedItem()}
                />
            );
        }

        return (
            <Fragment>
                <Select
                    onChange={this.selectedItems.bind(this)}
                    options={this.itemsToSelect()}
                    menuPosition={"absolute"}
                    menuPlacement={"bottom"}
                    styles={{
                        menuPortal: (base) => ({
                            ...base,
                            zIndex: 9999,
                            color: "#000000",
                        }),
                    }}
                    menuPortalTarget={document.body}
                    value={this.selectedItem()}
                />

                {this.state.selected_item !== 0 ? (
                    <RemoveGemComparison
                        comparison_data={
                            this.state.selected_gem_data.comparison
                                .atonement_changes
                        }
                        original_atonement={
                            this.state.selected_gem_data.comparison
                                .original_atonement
                        }
                        gems={this.state.selected_gem_data.gems}
                        character_id={this.props.character_id}
                        is_open={true}
                        item_name={this.getItemName()}
                        selected_item={this.state.selected_item}
                        update_parent={this.props.update_parent}
                        update_remomal_data={this.updateRemoveGemState.bind(
                            this,
                        )}
                        manage_modal={() =>
                            this.setState({
                                selected_item: 0,
                                selected_gem_data: null,
                            })
                        }
                    />
                ) : null}
            </Fragment>
        );
    }
}
