import React from 'react';
import {render} from 'react-dom';
import SiteAccessedAllTime from "../components/admin/statistics/site-accessed-all-time";

function renderStatsAllTime(id, label, title, api) {
  const signInStats = document.getElementById(id);

  render(
    <SiteAccessedAllTime label={label} title={title} apiUrl={api}/>,
    signInStats
  );
}

window.renderStatsAllTime = renderStatsAllTime;
