import React from 'react';
import {Card} from 'react-bootstrap';
import InventoryBase from "../inventory/inventory-base";

export default class InventoryDetails extends React.Component {

  constructor(props) {
    super(props);
  }

  render() {
    return (
      <>
        <Card>
          <Card.Body>
            <dl>
              <dt>Total gold:</dt>
              <dd>{this.props.characterSheet.gold}</dd>
              <dt>Total gold dust:</dt>
              <dd>{this.props.characterSheet.gold_dust}</dd>
              <dt>Total shards:</dt>
              <dd>{this.props.characterSheet.shards}</dd>
              <dt>Used / Max inventory space:</dt>
              <dd>{this.props.characterSheet.inventory_used} / {this.props.characterSheet.inventory_max}</dd>
              <dt>Stat to focus on for max damage:</dt>
              <dd>{this.props.characterSheet.damage_stat}</dd>
              <dt>To focus on for Hit%:</dt>
              <dd>Accuracy (skill) and {this.props.characterSheet.to_hit_stat}</dd>
            </dl>
          </Card.Body>
        </Card>
        <InventoryBase characterId={this.props.characterId} userId={this.props.userId} />
      </>
    );
  }
}