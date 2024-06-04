var EventType = (function () {
    function EventType() {}
    Object.defineProperty(EventType, "WEEKLY_CELESTIALS", {
        get: function () {
            return "Weekly Celestials";
        },
        enumerable: false,
        configurable: true,
    });
    Object.defineProperty(EventType, "MONTHLY_PVP", {
        get: function () {
            return "Monthly PVP";
        },
        enumerable: false,
        configurable: true,
    });
    Object.defineProperty(EventType, "WEEKLY_CURRENCY_DROPS", {
        get: function () {
            return "Weekly Currency Drops";
        },
        enumerable: false,
        configurable: true,
    });
    Object.defineProperty(EventType, "RAID_EVENT", {
        get: function () {
            return "Raid Event";
        },
        enumerable: false,
        configurable: true,
    });
    EventType.is = function (expected, actual) {
        return expected === actual;
    };
    return EventType;
})();
export default EventType;
//# sourceMappingURL=EventType.js.map
