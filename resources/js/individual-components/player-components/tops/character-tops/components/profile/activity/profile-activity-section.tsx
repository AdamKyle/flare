import React from "react";
import ProfileActivitySectionProps from "../../../types/profile/activity/profile-activity-section-props";
import TopsChartCard from "../sheet-inspect/tops-chart-card";

export default class ProfileActivitySection extends React.Component<ProfileActivitySectionProps> {
    render() {
        return (
            <section
                className="grid gap-4 lg:grid-cols-2"
                aria-label="Activity"
            >
                <TopsChartCard
                    title="Login Count"
                    description="Login sessions that began during each displayed period."
                    chart={this.props.activity?.login_count_chart}
                    xAxisLabel="Date"
                    yAxisLabel="Logins"
                    timeSeries={true}
                />
                <TopsChartCard
                    title="Login Duration"
                    description="Time logged in during each displayed period, calculated from recorded session intervals."
                    chart={this.props.activity?.login_duration_chart}
                    xAxisLabel="Date"
                    yAxisLabel="Hours"
                    timeSeries={true}
                />
            </section>
        );
    }
}
