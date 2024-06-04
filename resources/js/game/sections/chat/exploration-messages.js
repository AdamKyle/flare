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
import Messages from "./components/messages";
import clsx from "clsx";
var ExplorationMessages = (function (_super) {
    __extends(ExplorationMessages, _super);
    function ExplorationMessages(props) {
        return _super.call(this, props) || this;
    }
    ExplorationMessages.prototype.buildMessages = function () {
        return this.props.exploration_messages.map(function (message) {
            if (message.id !== 0 && message.id !== null) {
                return React.createElement(
                    "li",
                    {
                        className: clsx("my-2 break-word lg:break-normal ", {
                            italic: message.make_italic,
                            "text-blue-500 font-bold": message.is_reward,
                            "text-green-300": !message.is_reward,
                        }),
                        key: message.id,
                    },
                    message.message,
                );
            }
            return React.createElement(
                "li",
                {
                    className: clsx(
                        "my-2 break-word lg:break-normal text-green-300",
                        {
                            italic: message.make_italic,
                            "text-blue-500 font-bold": message.is_reward,
                        },
                    ),
                    key: message.id,
                },
                message.message,
            );
        });
    };
    ExplorationMessages.prototype.render = function () {
        return React.createElement(Messages, null, this.buildMessages());
    };
    return ExplorationMessages;
})(React.Component);
export default ExplorationMessages;
//# sourceMappingURL=exploration-messages.js.map
