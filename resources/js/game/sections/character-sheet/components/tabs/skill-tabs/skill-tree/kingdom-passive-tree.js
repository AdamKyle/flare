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
import React from "react";
import Node from "./node";
import { Tree, TreeNode } from "react-organizational-chart";
import TrainPassive from "../../../modals/skill-tree/train-passive";
var KingdomPassiveTree = (function (_super) {
    __extends(KingdomPassiveTree, _super);
    function KingdomPassiveTree(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            show_training_modal: false,
            skill: null,
        };
        return _this;
    }
    KingdomPassiveTree.prototype.buildNodes = function (passive) {
        var _this = this;
        var nodes = [];
        if (passive.children.length > 0) {
            nodes = passive.children.map(function (child) {
                return React.createElement(
                    TreeNode,
                    {
                        label: React.createElement(Node, {
                            passive: child,
                            show_passive_modal:
                                _this.showTrainingModal.bind(_this),
                            is_automation_running:
                                _this.props.is_automation_running,
                        }),
                    },
                    _this.buildNodes(child),
                );
            });
        }
        return nodes;
    };
    KingdomPassiveTree.prototype.showTrainingModal = function (skill) {
        this.setState({
            show_training_modal: !this.state.show_training_modal,
            skill: typeof skill === "undefined" ? null : skill,
        });
    };
    KingdomPassiveTree.prototype.render = function () {
        return React.createElement(
            "div",
            {
                className:
                    "overflow-x-auto overflow-y-hidden max-w-[300px] sm:max-w-[600px] md:max-w-[100%]",
            },
            React.createElement(
                Tree,
                {
                    lineWidth: "2px",
                    lineColor: "green",
                    lineBorderRadius: "10px",
                    label: React.createElement(Node, {
                        passive: this.props.passives,
                        show_passive_modal: this.showTrainingModal.bind(this),
                        is_automation_running: this.props.is_automation_running,
                    }),
                },
                this.buildNodes(this.props.passives),
            ),
            this.state.show_training_modal && this.state.skill !== null
                ? React.createElement(TrainPassive, {
                      is_open: this.state.show_training_modal,
                      manage_modal: this.showTrainingModal.bind(this),
                      skill: this.state.skill,
                      manage_success_message: this.props.manage_success_message,
                      update_passives: this.props.update_passives,
                      character_id: this.props.character_id,
                      is_dead: this.props.is_dead,
                  })
                : null,
        );
    };
    return KingdomPassiveTree;
})(React.Component);
export default KingdomPassiveTree;
//# sourceMappingURL=kingdom-passive-tree.js.map
