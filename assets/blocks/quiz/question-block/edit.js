import { InnerBlocks, RichText } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { useBlockIndex } from '../../../shared/blocks/block-index';

/**
 * Quiz question block editor.
 *
 * @param {Object}   props
 * @param {Object}   props.attributes
 * @param {Function} props.setAttributes
 */
export const EditQuestionBlock = ( props ) => {
	const {
		attributes: { title },
		setAttributes,
		clientId,
	} = props;

	const index = useBlockIndex( clientId );

	return (
		<div
			className={ `sensei-lms-question-block ${
				! title ? 'is-draft' : ''
			}` }
		>
			<h2 className="sensei-lms-question-block__index">{ index + 1 }.</h2>
			<RichText
				className="sensei-lms-question-block__title"
				tagName="h2"
				placeholder={ __( 'Add Question', 'sensei-lms' ) }
				value={ title }
				onChange={ ( nextValue ) =>
					setAttributes( { title: nextValue } )
				}
			/>
			<InnerBlocks
				template={ [
					[
						'core/paragraph',
						{
							placeholder: __(
								'Question Description',
								'sensei-lms'
							),
						},
					],
				] }
			/>
		</div>
	);
};
