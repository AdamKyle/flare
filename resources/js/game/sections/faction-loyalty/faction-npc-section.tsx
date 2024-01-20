import React from "react";
import {formatNumber} from "../../lib/game/format-number";
import FactionNpcSectionProps from "./types/faction-npc-section-props";

export default class FactionNpcSection extends React.Component<FactionNpcSectionProps, {  }> {

    constructor(props: any) {
        super(props);
    }

    render() {
        return (
          <>
              <h4>Rewards (when fame levels up)</h4>
              <dl className='my-2'>
                  <dt>XP</dt>
                  <dd>{formatNumber(this.props.faction_loyalty_npc.current_level > 0 ? this.props.faction_loyalty_npc.current_level * 1000 : 1000)}</dd>
                  <dt>Gold</dt>
                  <dd>{formatNumber(this.props.faction_loyalty_npc.current_level > 0 ? this.props.faction_loyalty_npc.current_level * 1000000 : 1000000)}</dd>
                  <dt>Gold Dust</dt>
                  <dd>
                      {formatNumber(this.props.faction_loyalty_npc.current_level > 0 ? this.props.faction_loyalty_npc.current_level * 1000 : 1000)}
                  </dd>
                  <dt>Shards</dt>
                  <dd>
                      {formatNumber(this.props.faction_loyalty_npc.current_level > 0 ? this.props.faction_loyalty_npc.current_level * 1000 : 1000)}
                  </dd>
                  <dt>Item Reward</dt>
                  <dd><a href='/information/random-enchants' target='_blank'>Medium Unique Item <i
                      className="fas fa-external-link-alt"></i></a></dd>
              </dl>

              <h4>Kingdom Item Defence Bonus</h4>
              <p className='my-4'>
                  Slowly accumulates as you level this NPC's fame.
              </p>
              <dl>
                  <dt>Defence Bonus per level</dt>
                  <dd>{(this.props.faction_loyalty_npc.kingdom_item_defence_bonus * 100).toFixed(0)}%</dd>
                  <dt>Current Defence Bonus</dt>
                  <dd>{(this.props.faction_loyalty_npc.current_kingdom_item_defence_bonus * 100).toFixed(0)}%</dd>
              </dl>
          </>
        );
    }
}
