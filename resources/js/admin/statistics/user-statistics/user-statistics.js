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
import LoginStatistics from "./components/login-statistics";
import BasicCard from "../../../game/components/ui/cards/basic-card";
import RegistrationStatistics from "./components/registration-statistics";
import OtherStatistics from "./components/other-statistics";
import CharacterReincarnationStatistics from "./components/character-reincarnation-statistics";
import CharacterTotalGold from "./components/character-total-gold";
import CharacterQuestCompletion from "./components/character-quest-completion";
var UserStatistics = (function (_super) {
    __extends(UserStatistics, _super);
    function UserStatistics(props) {
        return _super.call(this, props) || this;
    }
    UserStatistics.prototype.render = function () {
        return React.createElement(
            "div",
            { className: "pb-10" },
            React.createElement(
                "div",
                { className: "grid lg:grid-cols-2 gap-3 mb-5" },
                React.createElement(
                    BasicCard,
                    null,
                    React.createElement("h3", { className: "mb-4" }, "Logins"),
                    React.createElement(LoginStatistics, null),
                ),
                React.createElement(
                    BasicCard,
                    null,
                    React.createElement(
                        "h3",
                        { className: "mb-4" },
                        "Registrations",
                    ),
                    React.createElement(RegistrationStatistics, null),
                ),
            ),
            React.createElement(
                BasicCard,
                { additionalClasses: "mb-5" },
                React.createElement(
                    "h3",
                    { className: "mb-4" },
                    "Characters Who Reincarnated Once (or more)",
                ),
                React.createElement(CharacterReincarnationStatistics, null),
            ),
            React.createElement(
                BasicCard,
                { additionalClasses: "mb-5" },
                React.createElement(
                    "h3",
                    { className: "mb-4" },
                    "Character Quest Completion",
                ),
                React.createElement(CharacterQuestCompletion, null),
            ),
            React.createElement(
                BasicCard,
                { additionalClasses: "my-4" },
                React.createElement(
                    "h3",
                    { className: "mb-4" },
                    "Characters Gold",
                ),
                React.createElement(CharacterTotalGold, null),
            ),
            React.createElement(OtherStatistics, null),
        );
    };
    return UserStatistics;
})(React.Component);
export default UserStatistics;
//# sourceMappingURL=user-statistics.js.map
