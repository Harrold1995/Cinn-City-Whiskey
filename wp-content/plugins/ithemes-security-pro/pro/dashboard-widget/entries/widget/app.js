/**
 * WordPress dependencies
 */
import { compose } from '@wordpress/compose';
import { withSelect, withDispatch } from '@wordpress/data';
import { NoticeList } from '@wordpress/components';
import { PluginArea } from '@wordpress/plugins';
import '@wordpress/notices';

/**
 * Internal dependencies
 */
import '@ithemes/security.packages.data';
import '@ithemes/security.dashboard.api';
import { useRegisterCards } from '@ithemes/security.dashboard.dashboard';
import Carousel from './components/carousel';
import './style.scss';

function App( { dashboardId, notices, removeNotice } ) {
	useRegisterCards();

	return (
		<div className="itsec-dashboard">
			<NoticeList notices={ notices } onRemove={ removeNotice } />
			<Carousel dashboardId={ dashboardId } />
			<PluginArea />
		</div>
	);
}

export default compose( [
	withSelect( ( select ) => ( {
		dashboardId: select(
			'ithemes-security/dashboard'
		).getPrimaryDashboard(),
		notices: select( 'core/notices' ).getNotices( 'ithemes-security' ),
	} ) ),
	withDispatch( ( dispatch ) => ( {
		removeNotice( noticeId ) {
			return dispatch( 'core/notices' ).removeNotice(
				noticeId,
				'ithemes-security'
			);
		},
	} ) ),
] )( App );
