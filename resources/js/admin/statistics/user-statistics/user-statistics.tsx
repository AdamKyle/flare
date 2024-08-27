import React, { Fragment } from "react";
import LoginStatistics from "./components/login-statistics";
import BasicCard from "../../../game/components/ui/cards/basic-card";
import RegistrationStatistics from "./components/registration-statistics";
import CharacterGoldStatistics from "./components/character-quest-completion";
import OtherStatistics from "./components/other-statistics";
import CharacterReincarnationStatistics from "./components/character-reincarnation-statistics";
import CharacterTotalGold from "./components/character-total-gold";
import CharacterQuestCompletion from "./components/character-quest-completion";
import LoginDurationStatistics from "./components/login-duration-statistics";
import CharactersOnlineList from "./components/characters-online-list";

export default class UserStatistics extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <div className="pb-10">
                <div className="grid lg:grid-cols-2 gap-3 mb-5">
                    <BasicCard>
                        <h3 className="mb-4">Logins</h3>
                        <LoginStatistics />
                    </BasicCard>
                    <BasicCard>
                        <h3 className="mb-4">Registrations</h3>
                        <RegistrationStatistics />
                    </BasicCard>
                </div>
                <div className="grid lg:grid-cols-2 gap-3 mb-5">
                    <BasicCard additionalClasses={"mb-5"}>
                        <h3 className="mb-4">Login Duration Info</h3>
                        <LoginDurationStatistics />
                    </BasicCard>
                    <BasicCard>
                        <h3 className="mb-4">Who's online?</h3>
                        <CharactersOnlineList />
                    </BasicCard>
                </div>
                <BasicCard additionalClasses={"mb-5"}>
                    <h3 className="mb-4">
                        Characters Who Reincarnated Once (or more)
                    </h3>
                    <CharacterReincarnationStatistics />
                </BasicCard>
                <BasicCard additionalClasses={"mb-5"}>
                    <h3 className="mb-4">Character Quest Completion</h3>
                    <CharacterQuestCompletion />
                </BasicCard>
                <BasicCard additionalClasses={"my-4"}>
                    <h3 className="mb-4">Characters Gold</h3>
                    <CharacterTotalGold />
                </BasicCard>
                <OtherStatistics />
            </div>
        );
    }
}
