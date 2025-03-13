import React, { ChangeEvent } from "react";
import BasicCard from "../../../game/components/ui/cards/basic-card";
import Select, { ActionMeta, SingleValue } from "react-select";
import PrimaryButton from "../../../game/components/ui/buttons/primary-button";
import { isEqual, debounce } from "lodash";
import ComponentLoading from "../../../game/components/ui/loading/component-loading";
import SuccessButton from "../../../game/components/ui/buttons/success-button";
import OrangeButton from "../../../game/components/ui/buttons/orange-button";
import { Editor } from "@tinymce/tinymce-react";
import InfoSectionProps from "./types/info-section-props";
import InfoSectionState from "./types/info-section-state";
import SelectOption from "./types/select-option";
import { buildLivewireTableOptions } from "./utils/build-livewire-table-options";
import { buildItemTableOptions } from "./utils/build-item-table-options";

export default class InfoSection extends React.Component<
    InfoSectionProps,
    InfoSectionState
> {
    private debouncedUpdate: (content: string, callback: () => void) => void;

    constructor(props: InfoSectionProps) {
        super(props);

        this.state = {
            content: props.content.content ?? "",
            selected_live_wire_component:
                props.content.live_wire_component ?? null,
            selected_item_table_type: props.content.item_table_type ?? null,
            image_to_upload: null,
            order: props.content.order ?? 0,
            loading: true,
        };

        this.debouncedUpdate = debounce((content, callback) => {
            this.setState({ content }, () => {
                this.updateParentElement;

                if (callback) {
                    callback();
                }
            });
        }, 300);
    }

    componentDidMount(): void {
        setTimeout(() => {
            this.setState({ loading: false });
        }, 500);
    }

    componentDidUpdate(prevProps: Readonly<InfoSectionProps>): void {
        if (
            !isEqual(this.props.content.content, prevProps.content.content) &&
            this.props.content.content !== this.state.content
        ) {
            this.setState({ content: this.props.content.content ?? "" });
        }
    }

    setLivewireComponent(
        option: SingleValue<SelectOption>,
        _actionMeta: ActionMeta<SelectOption>,
    ): void {
        this.setState(
            { selected_live_wire_component: option?.value || null },
            this.updateParentElement,
        );
    }

    setItemTableType(
        option: SingleValue<SelectOption>,
        _actionMeta: ActionMeta<SelectOption>,
    ): void {
        this.setState(
            { selected_item_table_type: option?.value || null },
            this.updateParentElement,
        );
    }

    setOrder(e: ChangeEvent<HTMLInputElement>): void {
        this.setState(
            { order: parseInt(e.target.value, 10) || 0 },
            this.updateParentElement,
        );
    }

    updateParentElement = (): void => {
        this.props.update_parent_element(this.props.index, {
            live_wire_component: this.state.selected_live_wire_component,
            item_table_type: this.state.selected_item_table_type,
            content: this.state.content,
            content_image_path: this.state.image_to_upload,
            order: this.state.order,
        });
    };

    removeSection(): void {
        this.props.remove_section(this.props.index);
    }

    setFileForUpload(event: ChangeEvent<HTMLInputElement>): void {
        if (event.target.files && event.target.files.length > 0) {
            this.setState(
                { image_to_upload: event.target.files[0] },
                this.updateParentElement,
            );
        }
    }

    defaultSelectedAction(): SelectOption {
        return (
            buildLivewireTableOptions().find(
                (option) =>
                    option.value === this.state.selected_live_wire_component,
            ) || { label: "Please Select", value: "" }
        );
    }

    defaultSelectedItemType(): SelectOption {
        return (
            buildItemTableOptions().find(
                (option) =>
                    option.value === this.state.selected_item_table_type,
            ) || { label: "Please Select", value: "" }
        );
    }

    render(): JSX.Element {
        const { loading, content, order } = this.state;
        const {
            index,
            sections_length,
            add_section,
            save_and_finish,
            update_section,
        } = this.props;

        if (loading) {
            return <ComponentLoading />;
        }

        return (
            <BasicCard additionalClasses="mb-4">
                {index !== 0 && (
                    <div className="mb-5">
                        <button
                            type="button"
                            onClick={this.removeSection.bind(this)}
                            className="text-red-600 dark:text-red-500 absolute right-[5px] top-[5px]"
                        >
                            <i className="fas fa-times-circle"></i>
                        </button>
                    </div>
                )}

                <Editor
                    apiKey={import.meta.env.VITE_TINY_MCE_API_KEY}
                    init={{
                        plugins: "lists link image paste help wordcount",
                        toolbar:
                            "undo redo | blocks | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | help",
                    }}
                    initialValue={this.state.content ?? ""}
                    onEditorChange={(content, editor) => {
                        const bookmark = editor.selection.getBookmark(2);

                        this.debouncedUpdate(content, () => {
                            editor.selection.moveToBookmark(bookmark);
                        });
                    }}
                />

                <div className="my-5">
                    <label className="block mb-2 label">Order</label>
                    <input
                        type="number"
                        className="form-control"
                        onChange={this.setOrder.bind(this)}
                        value={order}
                    />
                </div>

                <div className="my-5">
                    <input
                        type="file"
                        className="form-control"
                        onChange={this.setFileForUpload.bind(this)}
                    />
                </div>

                <Select
                    onChange={this.setLivewireComponent.bind(this)}
                    options={buildLivewireTableOptions()}
                    menuPosition="absolute"
                    menuPlacement="bottom"
                    styles={{
                        menuPortal: (base) => ({
                            ...base,
                            zIndex: 9999,
                            color: "#000000",
                        }),
                    }}
                    menuPortalTarget={document.body}
                    value={this.defaultSelectedAction()}
                />

                <div className="my-4">
                    <Select
                        onChange={this.setItemTableType.bind(this)}
                        options={buildItemTableOptions()}
                        menuPosition="absolute"
                        menuPlacement="bottom"
                        styles={{
                            menuPortal: (base) => ({
                                ...base,
                                zIndex: 9999,
                                color: "#000000",
                            }),
                        }}
                        menuPortalTarget={document.body}
                        value={this.defaultSelectedItemType()}
                    />
                </div>

                <div className="flex justify-end mt-4">
                    {sections_length !== 1 && add_section === null && (
                        <div className="float-right">
                            <OrangeButton
                                button_label="Update Section"
                                on_click={() => update_section(index)}
                                additional_css="mr-4"
                            />
                        </div>
                    )}

                    {sections_length === 1 && index === 0 && (
                        <div className="float-right">
                            <SuccessButton
                                button_label="Save and Finish"
                                on_click={save_and_finish}
                                additional_css="mr-4"
                            />
                        </div>
                    )}

                    {index !== 0 && add_section !== null && (
                        <div className="float-right">
                            <SuccessButton
                                button_label="Save and Finish"
                                on_click={save_and_finish}
                                additional_css="mr-4"
                            />
                        </div>
                    )}

                    {add_section !== null && (
                        <div className="float-right">
                            <PrimaryButton
                                button_label="Add Section"
                                on_click={add_section}
                                additional_css="mr-4"
                            />
                        </div>
                    )}
                </div>
            </BasicCard>
        );
    }
}
