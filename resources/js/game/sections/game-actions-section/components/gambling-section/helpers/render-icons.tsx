import React from "react";
import IconType from "../../types/icon-type";

export const renderIcons = (index: number, icons: IconType[]): JSX.Element => {
    const icon = icons[index];

    return (
        <div className="text-center mb-10">
            <i
                className={icon.icon + " text-7xl"}
                style={{ color: icon.color }}
            ></i>
            <p className="text-lg mt-2">{icon.title}</p>
        </div>
    );
};
