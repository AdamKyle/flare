import Ajax from "../../../../game/lib/ajax/ajax";
var SiteStatisticsAjax = (function () {
    function SiteStatisticsAjax(component) {
        this.component = component;
    }
    SiteStatisticsAjax.prototype.fetchStatisticalData = function (
        routeName,
        daysPast,
    ) {
        var _this = this;
        new Ajax()
            .setRoute("admin/site-statistics/" + routeName)
            .setParameters({
                daysPast: daysPast,
            })
            .doAjaxCall(
                "get",
                function (result) {
                    _this.component.setState({
                        data: _this.component.createDataSet(
                            result.data.stats.data,
                            result.data.stats.labels,
                        ),
                        loading: false,
                    });
                },
                function (error) {
                    console.error(error);
                },
            );
    };
    SiteStatisticsAjax.prototype.createActionsDropDown = function (routeName) {
        var _this = this;
        return [
            {
                name: "Today",
                icon_class: "ra ra-bottle-vapors",
                on_click: function () {
                    return _this.fetchStatisticalData(routeName, 0);
                },
            },
            {
                name: "Last 7 Days",
                icon_class: "far fa-trash-alt",
                on_click: function () {
                    return _this.fetchStatisticalData(routeName, 6);
                },
            },
            {
                name: "Last 14 Days",
                icon_class: "far fa-trash-alt",
                on_click: function () {
                    return _this.fetchStatisticalData(routeName, 13);
                },
            },
            {
                name: "Last Month",
                icon_class: "far fa-trash-alt",
                on_click: function () {
                    return _this.fetchStatisticalData(routeName, 30);
                },
            },
        ];
    };
    return SiteStatisticsAjax;
})();
export default SiteStatisticsAjax;
//# sourceMappingURL=site-statistics-ajax.js.map
