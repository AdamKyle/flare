import React from "react";
import HealthBarProps from "./types/health-bar-props";
import { match } from "ts-pattern";
import { HealthBarType } from "./enums/health-bar-type";
import clsx from "clsx";

export default class HealthBar extends React.Component<HealthBarProps> {
    healthBarPercentage() {
        return (
            (this.props.current_health / this.props.max_health) *
            100
        ).toFixed(0);
    }

    fetchColor(): string {
        console.log(this.props.health_bar_type);
        return match(this.props.health_bar_type)
            .with(HealthBarType.ENEMY, () => "bg-rose-600 dark:bg-rose-500")
            .with(
                HealthBarType.PLAYER,
                () => "bg-emerald-600 dark:bg-emerald-500",
            )
            .otherwise(() => "");
    }

    render() {
        return (
            <div className="space-y-2 mb-4">
                <div className="flex justify-between text-sm font-medium text-gray-800 dark:text-gray-200">
                    <span id="character-name" className="sr-only">
                        {this.props.name}
                    </span>
                    <span>{this.props.name}</span>
                    <span aria-labelledby="character-name" aria-live="polite">
                        {this.props.current_health}/{this.props.max_health}
                    </span>
                </div>
                <div className="w-full bg-gray-300 dark:bg-gray-700 rounded-full h-2">
                    <div
                        className={clsx(
                            this.fetchColor(),
                            "rounded-full h-full",
                        )}
                        style={{ width: this.healthBarPercentage() + "%" }}
                        role="progressbar"
                        aria-valuenow={this.props.current_health}
                        aria-valuemin={0}
                        aria-valuemax={this.props.max_health}
                    ></div>
                </div>
            </div>
        );
    }
}
