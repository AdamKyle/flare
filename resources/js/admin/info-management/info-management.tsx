import React, { Fragment, ChangeEvent } from "react";
import InfoSection from "./info-section/info-section";
import DangerAlert from "../../game/components/ui/alerts/simple-alerts/danger-alert";
import Ajax from "../../game/lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import ManualProgressBar from "../../game/components/ui/progress-bars/manual-progress-bar";
import SuccessAlert from "../../game/components/ui/alerts/simple-alerts/success-alert";
import ComponentLoading from "../../game/components/ui/loading/component-loading";
import SuccessButton from "../../game/components/ui/buttons/success-button";
import DangerButton from "../../game/components/ui/buttons/danger-button";
import { cloneDeep, forEach, isEmpty, isEqual } from "lodash";
import InfoManagementProps from "./types/info-management-props";
import InfoManagementState from "./types/info-management-state";
import InfoSectionData from "./types/info-section-data";

export default class InfoManagement extends React.Component<
    InfoManagementProps,
    InfoManagementState
> {
    constructor(props: InfoManagementProps) {
        super(props);

        this.state = {
            info_sections: [],
            page_name: "",
            error_message: null,
            loading: false,
            posting: false,
            posting_index: 0,
            success_message: null,
        };
    }

    componentDidMount(): void {
        if (this.props.info_page_id !== 0) {
            this.fetchPageData();
        } else {
            this.addSection();
        }
    }

    fetchPageData = (): void => {
        this.setState({ loading: true }, () => {
            new Ajax()
                .setRoute("admin/info-section/page")
                .setParameters({
                    page_id: this.props.info_page_id,
                })
                .doAjaxCall(
                    "get",
                    (result: AxiosResponse) => {
                        this.setState({
                            page_name: result.data.page_name,
                            info_sections: result.data.page_sections,
                            loading: false,
                        });
                    },
                    (error: AxiosError) => {
                        this.setState({
                            loading: false,
                            error_message: "Failed to load page data.",
                        });
                    },
                );
        });
    };

    formatAndSendData = (section: InfoSectionData, redirect: boolean): void => {
        const form = new FormData();

        form.append("content", section.content || "");
        form.append("live_wire_component", section.live_wire_component || "");
        form.append("item_table_type", section.item_table_type || "");
        form.append("page_name", this.state.page_name);
        form.append("order", section.order.toString());

        if (section.content_image_path) {
            form.append("content_image", section.content_image_path);
        }

        if (this.props.info_page_id !== 0) {
            form.append("page_id", this.props.info_page_id.toString());
        }

        this.postForm(form, redirect);
    };

    updateSection = (index: number): void => {
        const sectionToUpdate = cloneDeep(this.state.info_sections[index]);
        this.formatAndSendData(sectionToUpdate, false);
    };

    postForm = (
        form: FormData,
        redirect: boolean,
        resolve?: () => void,
        reject?: () => void,
    ): void => {
        this.setState({ posting: true }, () => {
            this.post(form, redirect, resolve, reject);
        });
    };

    delete = (): void => {
        new Ajax()
            .setRoute("admin/info-section/delete-page")
            .setParameters({
                page_id: this.props.info_page_id,
            })
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    this.setState({
                        info_sections: result.data.page_sections,
                    });
                },
                (error: AxiosError) => {
                    this.setState({
                        error_message: "Failed to delete page.",
                    });
                },
            );
    };

    post = (
        form: FormData,
        redirect: boolean,
        resolve?: () => void,
        reject?: () => void,
    ): void => {
        const url =
            this.props.info_page_id !== 0
                ? "admin/info-section/update-page"
                : "admin/info-section/store-page";

        new Ajax()
            .setRoute(url)
            .setParameters(form)
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    const existingSections = cloneDeep(
                        this.state.info_sections,
                    );
                    const updatedSections = result.data.page_sections;

                    const mergedSections = [...updatedSections];

                    existingSections.forEach((section: InfoSectionData) => {
                        if (section.is_new_section) {
                            mergedSections.push(section);
                        }
                    });

                    this.setState({
                        posting: false,
                        info_sections: mergedSections,
                    });

                    if (redirect) {
                        window.location.href =
                            "/admin/information-management/page/" +
                            result.data.pageId;
                    }
                    if (resolve) resolve();
                },
                (error: AxiosError) => {
                    this.setState({
                        posting: false,
                        error_message: "Failed to save section.",
                    });
                    console.error(error);
                    if (reject) reject();
                },
            );
    };

    addNewSection = (form: FormData): void => {
        new Ajax()
            .setRoute("admin/info-section/add-section")
            .setParameters(form)
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    this.setState({
                        posting: false,
                        page_name: result.data.page_name,
                        info_sections: result.data.page_sections,
                    });
                },
                (error: AxiosError) => {
                    this.setState({
                        posting: false,
                        error_message: "Failed to add new section.",
                    });

                    console.error(error);
                },
            );
    };

    insertNewSectionAtIndex = (section: InfoSectionData): void => {
        const form = new FormData();

        if (this.props.info_page_id !== 0) {
            form.append("page_id", this.props.info_page_id.toString());
        }

        form.append("section_to_insert", JSON.stringify(section));

        this.addNewSection(form);
    };

    deleteSection = (order: number): void => {
        new Ajax()
            .setRoute(
                "admin/info-section/delete-section/" + this.props.info_page_id,
            )
            .setParameters({ order })
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    const sections = result.data.sections;
                    const stateSections = cloneDeep(this.state.info_sections);

                    stateSections.forEach(
                        (stateSection: InfoSectionData, index: number) => {
                            if (
                                !isEqual(
                                    stateSection.content,
                                    sections[index].content,
                                )
                            ) {
                                stateSections[index] = sections[index];
                            }
                            stateSections[index].order = parseInt(
                                sections[index].order,
                            );
                        },
                    );

                    this.setState({ info_sections: stateSections });
                },
                (error: AxiosError) => {
                    this.setState({
                        error_message: "Failed to delete section.",
                    });
                },
            );
    };

    setInfoSections = (index: number, content: InfoSectionData): void => {
        const sections = cloneDeep(this.state.info_sections);
        sections[index] = content;

        this.setState({ info_sections: sections });
    };

    addSection = (): void => {
        let infoSections = cloneDeep(this.state.info_sections);

        const lastEntry = infoSections[infoSections.length - 1];

        const newSection: InfoSectionData = {
            live_wire_component: null,
            item_table_type: null,
            content: null,
            content_image_path: null,
            is_new_section: true,
            order: 0,
        };

        if (lastEntry) {
            newSection.order = lastEntry.order + 1;
        }

        infoSections.push(newSection);

        infoSections = infoSections.map((infoSection) => {
            if (infoSection.is_new_section) {
                return infoSection;
            }

            infoSection.is_new_section = false;

            return infoSection;
        });

        this.setState({ info_sections: infoSections }, () => {
            if (!lastEntry) {
                return;
            }

            this.formatAndSendData(lastEntry, false);
        });
    };

    addSectionAbove = (index: number): void => {
        const infoSections = cloneDeep(this.state.info_sections);
        const newSection: InfoSectionData = {
            live_wire_component: null,
            item_table_type: null,
            content: null,
            content_image_path: null,
            is_new_section: true,
            order: infoSections[index]?.order || 0,
            insert_at_index: index,
        };

        infoSections.splice(index, 0, newSection);

        for (let i = index + 1; i < infoSections.length; i++) {
            if (!infoSections[i].is_new_section) {
                infoSections[i].new_order = infoSections[i].order + 1;
            }
        }

        for (let i = 0; i < infoSections.length; i++) {
            if (!infoSections[i].is_new_section) {
                infoSections[i].is_new_section = false;
            }
        }

        this.setState({ info_sections: infoSections }, () => {
            this.insertNewSectionAtIndex(newSection);
        });
    };

    saveAndFinish = (): void => {
        const infoSections = cloneDeep(this.state.info_sections);
        const sectionToSave = infoSections[infoSections.length - 1];

        this.formatAndSendData(sectionToSave, true);
    };

    removeSection = (index: number): void => {
        if (index <= 0) {
            return;
        }

        const infoSections = cloneDeep(this.state.info_sections);

        if (
            this.props.info_page_id !== 0 &&
            typeof infoSections[index].is_new_section === "undefined"
        ) {
            const section = infoSections[index];
            infoSections.splice(index, 1);
            this.deleteSection(section.order);
        } else {
            infoSections.splice(index, 1);
        }

        this.setState({ info_sections: infoSections });
    };

    setPageName = (event: ChangeEvent<HTMLInputElement>): void => {
        this.setState({ page_name: event.target.value });
    };

    goHome = (): void => {
        window.location.href = "/";
    };

    goBack = (): void => {
        if (this.props.info_page_id !== 0) {
            window.location.href =
                "/admin/information-management/page/" + this.props.info_page_id;
        } else {
            window.location.href = "/admin/information-management";
        }
    };

    renderContentSections = (): JSX.Element[] => {
        return this.state.info_sections.map(
            (
                infoSection: InfoSectionData,
                index: number,
                elements: InfoSectionData[],
            ) => {
                const isPosting =
                    index === this.state.posting_index && this.state.posting;

                return (
                    <InfoSection
                        key={`section-${index}`}
                        index={index}
                        sections_length={this.state.info_sections.length}
                        content={infoSection}
                        update_parent_element={this.setInfoSections}
                        remove_section={this.removeSection}
                        add_section_above={this.addSectionAbove}
                        add_section={
                            index === elements.length - 1
                                ? this.addSection
                                : null
                        }
                        save_and_finish={this.saveAndFinish}
                        update_section={this.updateSection}
                        is_posting={isPosting}
                    />
                );
            },
        );
    };

    render(): JSX.Element {
        const {
            loading,
            error_message,
            success_message,
            page_name,
            posting,
            posting_index,
            info_sections,
        } = this.state;
        const { info_page_id } = this.props;

        if (loading) {
            return (
                <div className="py-5">
                    <ComponentLoading />
                </div>
            );
        }

        return (
            <Fragment>
                <div className="grid grid-cols-2 gap-4 mb-5">
                    <h3 className="text-left">Content</h3>
                    <div className="text-right">
                        <SuccessButton
                            button_label="Home Section"
                            on_click={this.goHome}
                            additional_css="mr-2"
                        />
                        <SuccessButton
                            button_label="Back"
                            on_click={this.goBack}
                            additional_css="mr-2"
                        />
                        {info_page_id !== 0 && (
                            <DangerButton
                                button_label="Delete Page"
                                on_click={this.delete}
                            />
                        )}
                    </div>
                </div>

                {error_message !== null && (
                    <DangerAlert additional_css="my-4">
                        {error_message}
                    </DangerAlert>
                )}

                {success_message !== null && (
                    <SuccessAlert additional_css="my-4">
                        {success_message}
                    </SuccessAlert>
                )}

                <div className="my-5">
                    <label className="label block mb-2">Page Name</label>
                    <input
                        type="text"
                        className="form-control"
                        onChange={this.setPageName}
                        value={page_name}
                        disabled={info_page_id !== 0}
                    />
                </div>

                {posting && (
                    <div className="mt-4 mb-4">
                        <ManualProgressBar
                            label={`Posting #: ${posting_index}`}
                            secondary_label={`${posting_index}/${info_sections.length} sections posted`}
                            percentage_left={
                                posting_index / (info_sections.length - 1)
                            }
                            show_loading_icon={true}
                        />
                    </div>
                )}

                {this.renderContentSections()}
            </Fragment>
        );
    }
}
