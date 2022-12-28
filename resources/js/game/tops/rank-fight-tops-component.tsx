import { createRoot } from 'react-dom/client';
import React from "react";
import RankFightTops from "./rank-fight-tops";


const rankTops = document.getElementById('rank-tops-info');

if (rankTops !== null) {


    const root = createRoot(rankTops);

    root.render(<RankFightTops />);
}
