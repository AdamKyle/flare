var __extends =
    (this && this.__extends) ||
    (function () {
        var extendStatics = function (d, b) {
            extendStatics =
                Object.setPrototypeOf ||
                ({ __proto__: [] } instanceof Array &&
                    function (d, b) {
                        d.__proto__ = b;
                    }) ||
                function (d, b) {
                    for (var p in b)
                        if (Object.prototype.hasOwnProperty.call(b, p))
                            d[p] = b[p];
                };
            return extendStatics(d, b);
        };
        return function (d, b) {
            if (typeof b !== "function" && b !== null)
                throw new TypeError(
                    "Class extends value " +
                        String(b) +
                        " is not a constructor or null",
                );
            extendStatics(d, b);
            function __() {
                this.constructor = d;
            }
            d.prototype =
                b === null
                    ? Object.create(b)
                    : ((__.prototype = b.prototype), new __());
        };
    })();
import React, { Fragment } from "react";
import InfoSection from "./info-section/info-section";
import DangerAlert from "../../game/components/ui/alerts/simple-alerts/danger-alert";
import Ajax from "../../game/lib/ajax/ajax";
import ManualProgressBar from "../../game/components/ui/progress-bars/manual-progress-bar";
import SuccessAlert from "../../game/components/ui/alerts/simple-alerts/success-alert";
import ComponentLoading from "../../game/components/ui/loading/component-loading";
import SuccessButton from "../../game/components/ui/buttons/success-button";
import DangerButton from "../../game/components/ui/buttons/danger-button";
import { cloneDeep, isEqual } from "lodash";
var InfoManagement = (function (_super) {
    __extends(InfoManagement, _super);
    function InfoManagement(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            info_sections: [],
            page_name: "",
            error_message: null,
            loading: false,
            posting: false,
            posting_index: 0,
            success_message: null,
        };
        return _this;
    }
    InfoManagement.prototype.componentDidMount = function () {
        var _this = this;
        if (this.props.info_page_id !== 0) {
            this.setState(
                {
                    loading: true,
                },
                function () {
                    new Ajax()
                        .setRoute("admin/info-section/page")
                        .setParameters({
                            page_id: _this.props.info_page_id,
                        })
                        .doAjaxCall(
                            "get",
                            function (result) {
                                _this.setState({
                                    page_name: result.data.page_name,
                                    info_sections: result.data.page_sections,
                                    loading: false,
                                });
                            },
                            function (error) {},
                        );
                },
            );
        } else {
            this.addSection();
        }
    };
    InfoManagement.prototype.formatAndSendData = function (section, redirect) {
        var form = new FormData();
        form.append("content", section.content);
        form.append("live_wire_component", section.live_wire_component);
        form.append("item_table_type", section.item_table_type);
        form.append("page_name", this.state.page_name);
        form.append("order", section.order);
        if (section.content_image_path !== null) {
            form.append("content_image", section.content_image_path);
        }
        if (this.props.info_page_id !== 0) {
            form.append("page_id", this.props.info_page_id);
        }
        this.postForm(form, redirect);
    };
    InfoManagement.prototype.updateSection = function (index) {
        var sectionToUpdate = cloneDeep(this.state.info_sections[index]);
        this.formatAndSendData(sectionToUpdate, false);
    };
    InfoManagement.prototype.postForm = function (form, redirect) {
        var _this = this;
        this.setState(
            {
                posting: true,
            },
            function () {
                _this.post(form, redirect);
            },
        );
    };
    InfoManagement.prototype.delete = function () {
        new Ajax()
            .setRoute("admin/info-section/delete-page")
            .setParameters({
                page_id: this.props.info_page_id,
            })
            .doAjaxCall(
                "post",
                function (result) {
                    location.href = "/admin/information-management";
                },
                function (error) {},
            );
    };
    InfoManagement.prototype.post = function (form, redirect) {
        var _this = this;
        var url = "admin/info-section/store-page";
        if (this.props.info_page_id !== 0) {
            url = "admin/info-section/update-page";
        }
        new Ajax()
            .setRoute(url)
            .setParameters(form)
            .doAjaxCall(
                "post",
                function (result) {
                    _this.setState({
                        posting: false,
                    });
                    if (redirect) {
                        window.location.href =
                            "/admin/information-management/page/" +
                            result.data.pageId;
                    }
                },
                function (error) {
                    _this.setState({
                        posting: false,
                    });
                    console.error(error);
                },
            );
    };
    InfoManagement.prototype.deleteSection = function (order) {
        var _this = this;
        new Ajax()
            .setRoute(
                "admin/info-section/delete-section/" + this.props.info_page_id,
            )
            .setParameters({
                order: order,
            })
            .doAjaxCall(
                "post",
                function (result) {
                    var sections = result.data.sections;
                    var stateSections = cloneDeep(_this.state.info_sections);
                    stateSections.forEach(function (stateSection, index) {
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
                    });
                    _this.setState({
                        info_sections: stateSections,
                    });
                },
                function (error) {},
            );
    };
    InfoManagement.prototype.setInfoSections = function (index, content) {
        var sections = cloneDeep(this.state.info_sections);
        sections[index] = content;
        this.setState({
            info_sections: sections,
        });
    };
    InfoManagement.prototype.addSection = function () {
        var infoSections = cloneDeep(this.state.info_sections);
        var order = 1;
        infoSections.push({
            live_wire_component: null,
            content: null,
            content_image_path: null,
            is_new_section: true,
            order: order,
        });
        if (infoSections.length > 1) {
            var sectionToPublish = infoSections[infoSections.length - 2];
            infoSections[infoSections.length - 1].order =
                parseInt(sectionToPublish.order) + 1;
            this.formatAndSendData(sectionToPublish, false);
        }
        this.setState({
            info_sections: infoSections,
        });
    };
    InfoManagement.prototype.saveAndFinish = function () {
        var infoSections = cloneDeep(this.state.info_sections);
        var sectionToSave = infoSections[infoSections.length - 1];
        this.formatAndSendData(sectionToSave, true);
    };
    InfoManagement.prototype.removeSection = function (index) {
        if (index <= 0) {
            return;
        }
        var infoSections = cloneDeep(this.state.info_sections);
        if (
            this.props.info_page_id !== 0 &&
            typeof infoSections[index].is_new_section === "undefined"
        ) {
            var section = infoSections[index];
            infoSections.splice(index, 1);
            this.deleteSection(section.order);
        } else {
            infoSections.splice(index, 1);
        }
        this.setState({
            info_sections: infoSections,
        });
    };
    InfoManagement.prototype.setPageName = function (event) {
        this.setState({
            page_name: event.target.value,
        });
    };
    InfoManagement.prototype.goHome = function () {
        return (location.href = "/");
    };
    InfoManagement.prototype.goBack = function () {
        if (this.props.info_page_id !== 0) {
            return (location.href =
                "/admin/information-management/page/" +
                this.props.info_page_id);
        }
        return (location.href = "/admin/information-management");
    };
    InfoManagement.prototype.renderContentSections = function () {
        var _this = this;
        return this.state.info_sections.map(
            function (infoSection, index, elements) {
                return React.createElement(InfoSection, {
                    index: index,
                    sections_length: _this.state.info_sections.length,
                    content: infoSection,
                    update_parent_element: _this.setInfoSections.bind(_this),
                    remove_section: _this.removeSection.bind(_this),
                    add_section:
                        index === elements.length - 1
                            ? _this.addSection.bind(_this)
                            : null,
                    save_and_finish: _this.saveAndFinish.bind(_this),
                    update_section: _this.updateSection.bind(_this),
                });
            },
        );
    };
    InfoManagement.prototype.render = function () {
        if (this.state.loading) {
            return React.createElement(
                "div",
                { className: "py-5" },
                React.createElement(ComponentLoading, null),
            );
        }
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "div",
                { className: "grid grid-cols-2 gap-4 mb-5" },
                React.createElement(
                    "h3",
                    { className: "text-left" },
                    "Content",
                ),
                React.createElement(
                    "div",
                    { className: "text-right" },
                    React.createElement(SuccessButton, {
                        button_label: "Home Section",
                        on_click: this.goHome.bind(this),
                        additional_css: "mr-2",
                    }),
                    React.createElement(SuccessButton, {
                        button_label: "Back",
                        on_click: this.goBack.bind(this),
                        additional_css: "mr-2",
                    }),
                    this.props.info_page_id !== 0
                        ? React.createElement(DangerButton, {
                              button_label: "Delete Page",
                              on_click: this.delete.bind(this),
                          })
                        : null,
                ),
            ),
            this.state.error_message !== null
                ? React.createElement(
                      DangerAlert,
                      { additional_css: "my-4" },
                      this.state.error_message,
                  )
                : null,
            this.state.success_message !== null
                ? React.createElement(
                      SuccessAlert,
                      { additional_css: "my-4" },
                      this.state.success_message,
                  )
                : null,
            React.createElement(
                "div",
                { className: "my-5" },
                React.createElement(
                    "label",
                    { className: "label block mb-2" },
                    "Page Name",
                ),
                React.createElement("input", {
                    type: "text",
                    className: "form-control",
                    onChange: this.setPageName.bind(this),
                    value: this.state.page_name,
                    disabled: this.props.info_page_id !== 0,
                }),
            ),
            this.state.posting
                ? React.createElement(
                      "div",
                      { className: "mt-4 mb-4" },
                      React.createElement(ManualProgressBar, {
                          label: "Posting #: " + this.state.posting_index,
                          secondary_label:
                              this.state.posting_index +
                              "/" +
                              this.state.info_sections.length +
                              " sections posted",
                          percentage_left:
                              this.state.posting_index /
                              (this.state.info_sections.length - 1),
                          show_loading_icon: true,
                      }),
                  )
                : null,
            this.renderContentSections(),
        );
    };
    return InfoManagement;
})(React.Component);
export default InfoManagement;
//# sourceMappingURL=info-management.js.map
