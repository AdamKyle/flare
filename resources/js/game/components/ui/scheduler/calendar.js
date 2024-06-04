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
var __rest =
    (this && this.__rest) ||
    function (s, e) {
        var t = {};
        for (var p in s)
            if (Object.prototype.hasOwnProperty.call(s, p) && e.indexOf(p) < 0)
                t[p] = s[p];
        if (s != null && typeof Object.getOwnPropertySymbols === "function")
            for (
                var i = 0, p = Object.getOwnPropertySymbols(s);
                i < p.length;
                i++
            ) {
                if (
                    e.indexOf(p[i]) < 0 &&
                    Object.prototype.propertyIsEnumerable.call(s, p[i])
                )
                    t[p[i]] = s[p[i]];
            }
        return t;
    };
import React from "react";
import { Scheduler } from "@aldabil/react-scheduler";
import { Button } from "@mui/material";
var Calendar = (function (_super) {
    __extends(Calendar, _super);
    function Calendar(props) {
        return _super.call(this, props) || this;
    }
    Calendar.prototype.render = function () {
        if (!this.props.can_edit) {
            return React.createElement(Scheduler, {
                view: this.props.view,
                events: this.props.events,
                viewerExtraComponent: this.props.viewerExtraComponent,
                customEditor: this.props.customEditor,
                editable: false,
                deletable: false,
                disableViewNavigator: true,
                draggable: false,
                month: {
                    weekDays: [0, 1, 2, 3, 4, 5, 6],
                    weekStartOn: 6,
                    startHour: 0,
                    endHour: 23,
                    cellRenderer: function (_a) {
                        var height = _a.height,
                            start = _a.start,
                            onClick = _a.onClick,
                            props = __rest(_a, ["height", "start", "onClick"]);
                        return React.createElement(Button, {
                            style: {
                                height: "100%",
                                cursor: "not-allowed",
                            },
                        });
                    },
                },
            });
        }
        return React.createElement(Scheduler, {
            view: this.props.view,
            events: this.props.events,
            customEditor: this.props.customEditor,
            viewerExtraComponent: this.props.viewerExtraComponent,
            onDelete: this.props.onDelete,
        });
    };
    return Calendar;
})(React.Component);
export default Calendar;
//# sourceMappingURL=calendar.js.map
