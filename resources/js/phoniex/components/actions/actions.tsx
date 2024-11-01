import React from "react";
import Card from "../../ui/cards/card";
import IconSection from "./partials/icon-section";
import MonsterSection from "./partials/monster-section";

export default class Actions extends React.Component {
    render() {
        return (
            <Card>
                <div className="w-full flex flex-col lg:flex-row">
                    <IconSection />

                    <div className="flex flex-col items-center lg:items-start w-full">
                        <MonsterSection />
                    </div>
                </div>
            </Card>
        );
    }
}
