import React from "react";
import BasicCard from "../../../game/components/ui/cards/basic-card";
import LoginDurationChart from "./components/login-duration-chart";
import CharactersOnlineList from "./components/characters-online-list";

export default class CharactersOnlineContainer extends React.Component<
    any,
    any
> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <div className="pb-10">
                <div className="grid gap-3 mb-5 lg:grid-cols-2">
                    <BasicCard>
                        <h3 className="mb-4">AVG. User Login Duration</h3>
                        <LoginDurationChart />
                    </BasicCard>
                    <BasicCard>
                        <h3 className="mb-4">Who's Online?</h3>
                        <CharactersOnlineList />
                    </BasicCard>
                </div>
            </div>
        );
    }
}
