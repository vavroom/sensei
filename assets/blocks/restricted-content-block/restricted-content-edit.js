/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import RestrictedContentSettings from './restricted-content-settings';

export const RestrictOptions = {
	ENROLLED: 'enrolled',
	UNENROLLED: 'unenrolled',
	COURSE_COMPLETED: 'course-completed',
};

export const RestrictOptionLabels = {
	[ RestrictOptions.ENROLLED ]: __( 'Enrolled', 'sensei-lms' ),
	[ RestrictOptions.UNENROLLED ]: __( 'Not Enrolled', 'sensei-lms' ),
	[ RestrictOptions.COURSE_COMPLETED ]: __(
		'Course Completed',
		'sensei-lms'
	),
};

const RestrictedContentEdit = ( {
	className,
	hasInnerBlocks,
	clientId,
	attributes: { restrictionType },
	setAttributes,
} ) => {
	return (
		<>
			<div className={ classnames( 'wp-block-group', className ) }>
				<div className="wp-block-group__inner-container">
					<InnerBlocks
						renderAppender={
							! hasInnerBlocks && InnerBlocks.ButtonBlockAppender
						}
					/>
				</div>
			</div>
			<RestrictedContentSettings
				selectedRestriction={ restrictionType }
				onRestrictionChange={ ( option ) =>
					setAttributes( {
						restrictionType: option,
					} )
				}
				clientId={ clientId }
				hasInnerBlocks={ hasInnerBlocks }
			/>
		</>
	);
};

export default compose( [
	withSelect( ( select, { clientId } ) => {
		const { getBlock } = select( 'core/block-editor' );

		const block = getBlock( clientId );

		return {
			hasInnerBlocks: !! ( block && block.innerBlocks.length ),
		};
	} ),
] )( RestrictedContentEdit );
