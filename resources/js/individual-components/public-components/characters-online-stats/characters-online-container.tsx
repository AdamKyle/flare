import React from "react";
import BasicCard from "../../../game/components/ui/cards/basic-card";
import LoginDurationChart from "./components/login-duration-chart";
import CharactersOnlineList from "./components/characters-online-list";
import CharactersOnlineProps from "./types/characters-online-props";
import LoginStatistics from "./components/login-statistics";
import RegistrationStatistics from "./components/registration-statistics";

export default class CharactersOnlineContainer extends React.Component<CharactersOnlineProps> {
    constructor(props: CharactersOnlineProps) {
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
                    <BasicCard>
                        <h3 className="mb-4">
                            How many players have logged in?
                        </h3>
                        <LoginStatistics />
                    </BasicCard>
                    <BasicCard>
                        <h3 className="mb-4">
                            How many registrations have we had?
                        </h3>
                        <RegistrationStatistics />
                    </BasicCard>
                </div>
            </div>
        );
    }
}
