import {createRoot, Root} from 'react-dom/client';
import React from "react";
import Items from "./items";

const itemsTableComponent: HTMLElement | null = document.getElementById('items-table');

if (itemsTableComponent !== null) {

    const dataAttribute = itemsTableComponent.getAttribute('data-item-table-type');

    const root: Root = createRoot(itemsTableComponent);

    root.render(<Items type={dataAttribute} />);
}
