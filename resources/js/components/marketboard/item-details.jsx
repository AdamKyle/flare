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
        <div className='col-sm-12'>
          <div className="alert alert-info">
            There are no affixes on this {this.props.item.name}
          </div>
        </div>
      );
    }

    let colSize = 12;

    return (
      <>
        {
          this.props.item.item_prefix !== null ?
            <div className={this.props.item.item_suffix !== null ? 'col-md-6' : 'col-md-12'}>
              <AffixData item={this.props.item} type={'prefix'} />
            </div>
          : null
        }

        {
          this.props.item.item_suffix !== null ?
            <div className={this.props.item.item_prefix !== null ? 'col-md-6' : 'col-md-12'}>
              <AffixData item={this.props.item} type={'suffix'} />
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
        {!this.props.item.usable && !this.props.item.can_use_on_other_items ?
          <Tab eventKey="holy" title="Holy Bonuses">
            <Card>
              <Card.Body>
                <Row>
                  <Col xs={12}>
                    <dl>
                      <dt>Holy Bonus</dt>
                      <dd>{this.props.item.holy_stacks_applied > 0 ? ((this.props.item.holy_stacks_applied / this.props.item.holy_stacks) * 100).toFixed(2) : 0.0}%</dd>
                      <dt>Stacks Applied</dt>
                      <dd>{this.props.item.holy_stacks_applied}</dd>
                      <dt>Stacks Left</dt>
                      <dd>{this.props.item.holy_stacks - this.props.item.holy_stacks_applied}</dd>
                      <dt>Stat Bonus</dt>
                      <dd>{(this.props.item.holy_stack_stat_bonus * 100).toFixed(2)}%</dd>
                      <dt>Devouring Darkness Bonus:</dt>
                      <dd>{(this.props.item.holy_stack_devouring_darkness * 100).toFixed(2)}%</dd>
                    </dl>
                  </Col>
                </Row>
              </Card.Body>
            </Card>
          </Tab>
        : null
        }
      </Tabs>
    );
  }
}
