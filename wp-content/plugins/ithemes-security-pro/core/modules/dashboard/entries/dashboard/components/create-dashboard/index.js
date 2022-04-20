/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { TextControl, Button } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { useSelect, useDispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import DefaultLayout from './default-layout.svg';
import ScratchLayout from './scratch-layout.svg';
import './style.scss';

export default function CreateDashboard() {
	const instanceId = useInstanceId( CreateDashboard );

	const [ defaultLabel, setDefaultLabel ] = useState( '' );
	const [ scratchLabel, setStratchLabel ] = useState( '' );

	const { canCreate, canCreateLoaded, addingScratch, addingDefault } = useSelect( ( select ) => ( {
		canCreate: select( 'ithemes-security/dashboard' ).canCreateDashboards(),
		canCreateLoaded: select(
			'ithemes-security/dashboard'
		).isCanCreateDashboardsLoaded(),
		addingScratch: select( 'ithemes-security/dashboard' ).isAddingDashboard(
			'create-dashboard-scratch'
		),
		addingDefault: select( 'ithemes-security/dashboard' ).isAddingDashboard(
			'create-dashboard-default'
		),
	} ) );

	const { addDashboard: add } = useDispatch( 'ithemes-security/dashboard' );

	if ( ! canCreate && canCreateLoaded ) {
		return (
			<div className="itsec-create-dashboard">
				<p>
					{ __(
						'You don’t have permission to create new dashboards. Try switching to a dashboard or ask an administrator to invite you to one.',
						'it-l10n-ithemes-security-pro'
					) }
				</p>
			</div>
		);
	}

	const create = ( type ) => ( e ) => {
		e.preventDefault();

		const dashboard = {};

		switch ( type ) {
			case 'scratch':
				dashboard.label = scratchLabel;
				break;
			case 'default':
				dashboard.label = defaultLabel;
				dashboard.preset = 'default';
				break;
			default:
				return;
		}

		add( dashboard, `create-dashboard-${ type }` );
	};

	return (
		<div className="itsec-create-dashboard">
			<section className="itsec-create-dashboard__start itsec-create-dashboard__start--default">
				<header>
					<DefaultLayout height={ 100 } />
					<h2>{ __( 'Start with the default layout.', 'it-l10n-ithemes-security-pro' ) }</h2>
					<p>
						{ __(
							'You can continue to customize this later.',
							'it-l10n-ithemes-security-pro'
						) }
					</p>
				</header>
				<form onSubmit={ create( 'default' ) }>
					<TextControl
						className="itsec-create-dashboard__name"
						label={ __( 'Dashboard Name', 'it-l10n-ithemes-security-pro' ) }
						placeholder={ __( 'Dashboard Name…', 'it-l10n-ithemes-security-pro' ) }
						id={ `itsec-create-dashboard__name--default-${ instanceId }` }
						value={ defaultLabel }
						onChange={ ( label ) =>
							setDefaultLabel( label )
						}
						disabled={ addingDefault || addingScratch }
					/>
					<div className="itsec-create-dashboard__trigger-container">
						<Button
							className="itsec-create-dashboard__trigger"
							type="submit"
							isBusy={ addingDefault }
							disabled={ addingScratch }
						>
							{ __( 'Create Board', 'it-l10n-ithemes-security-pro' ) }
						</Button>
					</div>
				</form>
			</section>

			<section className="itsec-create-dashboard__start itsec-create-dashboard__start--scratch">
				<header>
					<ScratchLayout
						height={ 100 }
						className="itsec-create-dashboard__scratch-icon"
					/>
					<h2>{ __( 'Start from Scratch.', 'it-l10n-ithemes-security-pro' ) }</h2>
					<p>
						{ __(
							'Start building a dashboard with security cards.',
							'it-l10n-ithemes-security-pro'
						) }
					</p>
				</header>
				<form onSubmit={ create( 'scratch' ) }>
					<TextControl
						className="itsec-create-dashboard__name"
						label={ __( 'Dashboard Name', 'it-l10n-ithemes-security-pro' ) }
						placeholder={ __( 'Dashboard Name…', 'it-l10n-ithemes-security-pro' ) }
						id={ `itsec-create-dashboard__name--name-${ instanceId }` }
						value={ scratchLabel }
						onChange={ ( label ) =>
							setStratchLabel( label )
						}
						disabled={ addingDefault || addingScratch }
					/>
					<div className="itsec-create-dashboard__trigger-container">
						<Button
							className="itsec-create-dashboard__trigger"
							type="submit"
							isBusy={ addingScratch }
							disabled={ addingDefault }
						>
							{ __( 'Create Board', 'it-l10n-ithemes-security-pro' ) }
						</Button>
					</div>
				</form>
			</section>
		</div>
	);
}
