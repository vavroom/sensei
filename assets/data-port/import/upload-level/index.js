import { withSelect, withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { UploadLevels } from './upload-level';

export default compose(
	withSelect( ( select ) => {
		const store = select( 'sensei/import' );

		return {
			jobId: store.getJobId(),
			state: store.getStepData( 'upload' ),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { uploadFileForLevel, throwEarlyUploadError } = dispatch(
			'sensei/import'
		);

		return {
			uploadFileForLevel,
			throwEarlyUploadError,
		};
	} )
)( UploadLevels );
