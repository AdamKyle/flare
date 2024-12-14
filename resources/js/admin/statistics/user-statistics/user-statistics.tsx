import React from "react";
import OtherStatistics from "./components/other-statistics";
import CharacterReincarnationStatistics from "./components/character-reincarnation-statistics";
import CharacterTotalGold from "./components/character-total-gold";
import CharacterQuestCompletion from "./components/character-quest-completion";
import CharactersOnlineContainer from "../../../individual-components/public-components/characters-online-stats/characters-online-container";
import BasicCard from "../../components/ui/cards/basic-card";

export default class UserStatistics extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <div className="pb-10">
                <CharactersOnlineContainer />
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
