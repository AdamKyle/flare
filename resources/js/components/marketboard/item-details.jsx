import React from 'react';
import {Card, Row, Col, Tabs, Tab} from 'react-bootstrap';
import BaseDetails from "./partials/base-details";
import EquipDetails from "./partials/equip-details";
import AffixData from "./partials/affix-data";

export default class ItemDetails extends React.Component {

  constructor(props) {
    super(props)
  }

  formatFloat(float) {
    return float.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
  }

  renderAffixes() {
    if (this.props.item.item_prefix === null && this.props.item.item_suffix === null) {
      return (
        <div className="alert alert-info">
          There are no affixes on this this.props.item.
        </div>
      );
    }

    let colSize = 12;

    return (
      <>
        {
          this.props.item.item_prefix !== null ?
            <div className={this.props.item.item_suffix !== null ? 'col-md-6' : 'col-md-12'}>
              <Card>
                <Card.Body>
                  <AffixData item={this.props.item} type={'prefix'} />
                </Card.Body>
              </Card>
            </div>
          : null
        }

        {
          this.props.item.item_suffix !== null ?
            <div className={this.props.item.item_prefix !== null ? 'col-md-6' : 'col-md-12'}>
              <Card>
                <Card.Body>
                  <AffixData item={this.props.item} type={'suffix'} />
                </Card.Body>
              </Card>
            </div>
          : null
        }
      </>
    )
  }

  render() {
    return (
      <Tabs defaultActiveKey="details" id="details" className="mb-3">
        <Tab eventKey="details" title="Details">
          <Card>
            <Card.Body>
              <BaseDetails item={this.props.item} />
            </Card.Body>
          </Card>
        </Tab>
        <Tab eventKey="base-equip" title="Base Equip">
          <Card>
            <Card.Body>
              <EquipDetails item={this.props.item} />
            </Card.Body>
          </Card>
        </Tab>
        <Tab eventKey="affixes" title="Affixes">
          <Card>
            <Card.Body>
              <Row>
                {this.renderAffixes()}
              </Row>
            </Card.Body>
          </Card>
        </Tab>
      </Tabs>
    );
  }
}
