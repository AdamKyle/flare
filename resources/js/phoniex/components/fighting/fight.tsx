import React from "react";
import BaseMonsterSection from "./components/base-monster-section";
import AttackButtons from "./components/attack-buttons";
import AttackLog from "./components/attack-log";
import DangerButton from "../../ui/buttons/danger-button";

interface FightState {
    fightInitiated: boolean;
}

export default class Fight extends React.Component<{}, FightState> {
    state: FightState = {
        fightInitiated: false,
    };

    initiateFight = () => {
        this.setState({ fightInitiated: true });
    };

    render() {
        const { fightInitiated } = this.state;

        return (
            <div className="max-w-md mx-auto p-4 bg-gray-100 dark:bg-gray-800 rounded-lg shadow-md">
                <BaseMonsterSection />

                {fightInitiated ? (
                    <AttackButtons />
                ) : (
                    <div className="flex justify-center mt-6">
                        <DangerButton
                            on_click={this.initiateFight.bind(this)}
                            label={"Initiate Fight"}
                        />
                    </div>
                )}

                <AttackLog />
            </div>
        );
    }
}
