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
var AnnouncementMessages = (function (_super) {
    __extends(AnnouncementMessages, _super);
    function AnnouncementMessages(props) {
        return _super.call(this, props) || this;
    }
    AnnouncementMessages.prototype.buildMessages = function () {
        return this.props.announcements.map(function (message) {
            return React.createElement(
                "li",
                {
                    className:
                        "my-2 break-word lg:break-normal text-orange-500",
                    key: message.id,
                },
                message.message,
            );
        });
    };
    AnnouncementMessages.prototype.render = function () {
        return React.createElement(Messages, null, this.buildMessages());
    };
    return AnnouncementMessages;
})(React.Component);
export default AnnouncementMessages;
//# sourceMappingURL=announcement-messages.js.map
