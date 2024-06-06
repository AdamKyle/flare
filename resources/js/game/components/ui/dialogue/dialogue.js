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
import { Dialog, Transition } from "@headlessui/react";
import DangerButton from "../buttons/danger-button";
import PrimaryButton from "../buttons/primary-button";
import clsx from "clsx";
var Dialogue = (function (_super) {
    __extends(Dialogue, _super);
    function Dialogue(props) {
        return _super.call(this, props) || this;
    }
    Dialogue.prototype.closeModal = function () {
        if (
            typeof this.props.handle_close !== "undefined" &&
            this.props.is_open
        ) {
            this.props.handle_close();
        }
    };
    Dialogue.prototype.render = function () {
        var _this = this;
        return React.createElement(
            Transition,
            { appear: true, show: this.props.is_open, as: Fragment },
            React.createElement(
                Dialog,
                {
                    as: "div",
                    className: "absolute inset-0 z-50",
                    onClose: function () {
                        _this.closeModal();
                    },
                },
                React.createElement(Dialog.Overlay, {
                    className: "fixed inset-0 bg-black opacity-30",
                }),
                React.createElement(
                    "div",
                    { className: "min-h-screen px-4 text-center" },
                    React.createElement(
                        Transition.Child,
                        {
                            as: Fragment,
                            enter: "ease-out duration-300",
                            enterFrom: "opacity-0",
                            enterTo: "opacity-100",
                            leave: "ease-in duration-200",
                            leaveFrom: "opacity-100",
                            leaveTo: "opacity-0",
                        },
                        React.createElement(Dialog.Overlay, {
                            className: "fixed inset-0",
                        }),
                    ),
                    React.createElement(
                        Transition.Child,
                        {
                            as: Fragment,
                            enter: "ease-out duration-300",
                            enterFrom: "opacity-0 scale-95",
                            enterTo: "opacity-100 scale-100",
                            leave: "ease-in duration-200",
                            leaveFrom: "opacity-100 scale-100",
                            leaveTo: "opacity-0 scale-95",
                        },
                        React.createElement(
                            "div",
                            {
                                className:
                                    "fixed inset-0 flex items-center justify-center p-4",
                            },
                            React.createElement(
                                "div",
                                {
                                    className:
                                        "flex min-h-full min-w-full items-center justify-center",
                                },
                                React.createElement(
                                    "div",
                                    {
                                        className: clsx(
                                            "inline-block w-full p-6 my-8 text-left align-middle transition-all transform bg-white dark:bg-gray-700 drop-shadow-xl rounded-md",
                                            {
                                                "max-w-7xl":
                                                    this.props.large_modal &&
                                                    !this.props.medium_modal,
                                            },
                                            {
                                                "max-w-5xl":
                                                    this.props.medium_modal,
                                            },
                                            {
                                                "max-w-3xl":
                                                    !this.props.large_modal &&
                                                    !this.props.medium_modal,
                                            },
                                        ),
                                    },
                                    React.createElement(
                                        Dialog.Title,
                                        {
                                            as: "span",
                                            className:
                                                "flex items-center text-lg font-medium leading-6 text-gray-700 dark:text-gray-500 relative mb-5 text-[16px] lg:text-xl",
                                        },
                                        this.props.title,
                                        React.createElement(
                                            "button",
                                            {
                                                className:
                                                    "flex items-center absolute right-[20px] cursor-pointer hover:text-gray-800 dark:hover:text-gray-600 top-[5px]",
                                                onClick:
                                                    this.closeModal.bind(this),
                                                disabled:
                                                    this.props
                                                        .primary_button_disabled,
                                            },
                                            React.createElement("i", {
                                                className: "fas fa-times ",
                                            }),
                                        ),
                                    ),
                                    React.createElement(
                                        "div",
                                        { className: "mt-2" },
                                        React.createElement("div", {
                                            className:
                                                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                                        }),
                                        this.props.children,
                                    ),
                                    React.createElement("div", {
                                        className:
                                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                                    }),
                                    React.createElement(
                                        "div",
                                        { className: "mt-4" },
                                        typeof this.props.handle_close !==
                                            "undefined"
                                            ? React.createElement(
                                                  DangerButton,
                                                  {
                                                      button_label: "Cancel",
                                                      on_click:
                                                          this.closeModal.bind(
                                                              this,
                                                          ),
                                                      disabled:
                                                          this.props
                                                              .primary_button_disabled,
                                                  },
                                              )
                                            : null,
                                        typeof this.props.secondary_actions !==
                                            "undefined" &&
                                            this.props.secondary_actions !==
                                                null
                                            ? React.createElement(
                                                  PrimaryButton,
                                                  {
                                                      additional_css:
                                                          "float-right",
                                                      button_label:
                                                          this.props
                                                              .secondary_actions
                                                              .secondary_button_label,
                                                      on_click:
                                                          this.props
                                                              .secondary_actions
                                                              .handle_action,
                                                      disabled:
                                                          this.props
                                                              .secondary_actions
                                                              .secondary_button_disabled,
                                                  },
                                              )
                                            : null,
                                        typeof this.props.tertiary_actions !==
                                            "undefined"
                                            ? React.createElement(
                                                  PrimaryButton,
                                                  {
                                                      additional_css:
                                                          "mr-2 float-right",
                                                      button_label:
                                                          this.props
                                                              .tertiary_actions
                                                              .tertiary_button_label,
                                                      on_click:
                                                          this.props
                                                              .tertiary_actions
                                                              .handle_action,
                                                      disabled:
                                                          this.props
                                                              .tertiary_actions
                                                              .tertiary_button_disabled,
                                                  },
                                              )
                                            : null,
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        );
    };
    return Dialogue;
})(React.Component);
export default Dialogue;
//# sourceMappingURL=dialogue.js.map
