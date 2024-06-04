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
import QuestDetailsModal from "../modals/quest-details-modal";
import clsx from "clsx";
var QuestNode = (function (_super) {
    __extends(QuestNode, _super);
    function QuestNode(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            open_quest_modal: false,
        };
        return _this;
    }
    QuestNode.prototype.showQuestDetails = function () {
        this.setState({
            open_quest_modal: !this.state.open_quest_modal,
        });
    };
    QuestNode.prototype.isQuestCompleted = function () {
        if (this.props.quest !== null) {
            return this.props.completed_quests.includes(this.props.quest.id);
        }
        return false;
    };
    QuestNode.prototype.isParentQuestComplete = function () {
        if (this.props.quest !== null) {
            if (this.props.quest.is_parent) {
                return true;
            }
            return this.props.completed_quests.includes(
                this.props.quest.parent_quest_id,
            );
        }
        return false;
    };
    QuestNode.prototype.isRequiredQuestComplete = function () {
        if (this.props.quest !== null) {
            if (this.props.quest.required_quest_id !== null) {
                var completedQuests = this.props.completed_quests;
                return completedQuests.includes(
                    this.props.quest.required_quest_id,
                );
            }
        }
        return true;
    };
    QuestNode.prototype.render = function () {
        var _a, _b;
        return React.createElement(
            "div",
            null,
            React.createElement(
                "button",
                {
                    type: "button",
                    role: "button",
                    className: clsx(
                        {
                            "text-yellow-700 dark:text-yellow-600":
                                !this.isRequiredQuestComplete() &&
                                this.isParentQuestComplete(),
                        },
                        {
                            "text-blue-500 dark:text-blue-400":
                                !this.isQuestCompleted() &&
                                this.isParentQuestComplete(),
                        },
                        {
                            "text-green-700 dark:text-green-600":
                                this.isQuestCompleted(),
                        },
                        {
                            "text-red-500 dark:text-red-400":
                                !this.isParentQuestComplete(),
                        },
                    ),
                    onClick: this.showQuestDetails.bind(this),
                },
                (_a = this.props.quest) === null || _a === void 0
                    ? void 0
                    : _a.name,
            ),
            this.state.open_quest_modal
                ? React.createElement(QuestDetailsModal, {
                      is_open: this.state.open_quest_modal,
                      handle_close: this.showQuestDetails.bind(this),
                      quest_id:
                          (_b = this.props.quest) === null || _b === void 0
                              ? void 0
                              : _b.id,
                      character_id: this.props.character_id,
                      is_parent_complete: this.isParentQuestComplete(),
                      is_quest_complete: this.isQuestCompleted(),
                      is_required_quest_complete:
                          this.isRequiredQuestComplete(),
                      update_quests: this.props.update_quests,
                  })
                : null,
        );
    };
    return QuestNode;
})(React.Component);
export default QuestNode;
//# sourceMappingURL=quest-node.js.map
