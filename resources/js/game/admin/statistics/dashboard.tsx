import React from "react";
import UserStatistics from "./user-statistics/user-statistics";

export default class Dashboard extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <UserStatistics />
        )
    }
}
