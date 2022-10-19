import React from "react";
import BasicCard from "../../components/ui/cards/basic-card";
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
