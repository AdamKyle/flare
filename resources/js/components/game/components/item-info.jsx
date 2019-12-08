import React from 'react';

export default class ItemInfo extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      item: this.props.item,
    }
  }

  renderAffixes(item) {
    return item.item_affixes.map((affix) => {
      return (
        <div key ={affix.id}>
          <dl>
            <dt>Name:</dt>
            <dd>{affix.name}</dd>
          </dl>
          <dl>
            <dt>Base Damage Mod:</dt>
            <dd>{'+' + affix.base_damage_mod}</dd>
          </dl>
          <div className="mt-2 mb-2 text-center"><i>{affix.description}</i></div>
        </div>
      )
    })
  }

  render() {
    const item = this.state.item;

    return (
      <div className="item-info">
        <div className="row">
          <div className="col-md-12">
            <div className="card">
              <div className="card-header">
                <h6>{item.name}</h6>
              </div>
              <div className="card-body">
                {item.type !== 'quest'
                 ?
                 <dl className>
                   <dt>Base Damage:</dt>
                   <dd>{item.base_damage}</dd>
                 </dl>
                 : null
               }
                <dl>
                  <dt>Type:</dt>
                  <dd>{item.type}</dd>
                </dl>
                {item.artifact_property !== null
                 ?
                  <>
                   <hr />
                   <h5>Artifact Details</h5>
                   <dl>
                     <dt>Name:</dt>
                     <dd>{item.artifact_property.name}</dd>
                   </dl>
                   <dl>
                     <dt>Base Damage Mod:</dt>
                     <dd>{'+' + item.artifact_property.base_damage_mod}</dd>
                   </dl>
                   <div className="mt-2 mb-2 text-center"><i>{item.artifact_property.description}</i></div>
                   <hr />
                  </>
                 : null
                }
                {item.item_affixes.length > 0
                 ?
                  <>
                   <h5>Item Affixes</h5>
                   {this.renderAffixes(item)}
                   <hr />
                  </>
                 : null
                }
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}
