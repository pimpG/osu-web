/**
 *    Copyright (c) ppy Pty Ltd <contact@ppy.sh>.
 *
 *    This file is part of osu!web. osu!web is distributed with the hope of
 *    attracting more community contributions to the core ecosystem of osu!.
 *
 *    osu!web is free software: you can redistribute it and/or modify
 *    it under the terms of the Affero GNU General Public License version 3
 *    as published by the Free Software Foundation.
 *
 *    osu!web is distributed WITHOUT ANY WARRANTY; without even the implied
 *    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *    See the GNU Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public License
 *    along with osu!web.  If not, see <http://www.gnu.org/licenses/>.
 */

import * as React from 'react';

interface Props {
  render(renderProps: RenderProps): React.ReactNode[];
}

interface RenderProps {
  state: State;
  update(params: RenderPropsUpdateParams): void; // a callback to update the activated state of the wrapper.
}

interface RenderPropsUpdateParams {
  active: boolean; //  the state it was updated to.
  index: any; // the index that was updated.
}

interface State {
  activeIndex?: any;
}

/**
 * A wrapper component for tracking which menu in a list is 'active'.
 * TODO: should probably move to a context provider.
 */
export class MenuActive extends React.PureComponent<Props, State> {
  readonly state: State = {};

  update = (params: RenderPropsUpdateParams) => {
    this.setState({ activeIndex: params.active ? params.index : null });
  }

  render() {
    const { state, update } = this;
    return (
      <>
        {this.props.render({ state, update })}
      </>
    );
  }
}
