import { apiFetch, controls as dataControls } from '@wordpress/data-controls';
import { dispatch, registerStore, select, subscribe } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { createReducerFromActionMap } from '../../shared/data/store-helpers';
import { isEqual } from 'lodash';

const DEFAULT_STATE = {
	structure: null,
	editor: [],
	isSaving: false,
	isEditorDirty: false,
	hasChanges: false,
	isSaveCalled: false,
};

const actions = {
	/**
	 * Fetch course structure data from REST API.
	 */
	*fetchCourseStructure() {
		const courseId = yield select( 'core/editor' ).getCurrentPostId();
		const result = yield apiFetch( {
			path: `/sensei-internal/v1/course-structure/${ courseId }?context=edit`,
		} );
		yield actions.setStructure( result );
	},
	/**
	 * Persist editor's course structure to the REST API
	 */
	*save() {
		const { getEditorStructure } = select( COURSE_STORE );

		yield { type: 'SAVING', isSaving: true };
		const courseId = yield select( 'core/editor' ).getCurrentPostId();
		try {
			const result = yield apiFetch( {
				path: `/sensei-internal/v1/course-structure/${ courseId }`,
				method: 'POST',
				data: { structure: yield getEditorStructure() },
			} );
			yield actions.setStructure( result );
		} catch ( error ) {
			const errorMessage = sprintf(
				/* translators: Error message. */
				__(
					'Course modules and lessons could not be updated. %s',
					'sensei-lms'
				),
				error.message
			);
			yield dispatch( 'core/notices' ).createErrorNotice( errorMessage, {
				id: 'course-outline-save-error',
			} );
		}

		yield { type: 'SAVING', isSaving: false };
	},
	setStructure: ( structure ) => ( { type: 'SET_SERVER', structure } ),
	setEditorStructure: ( structure ) => {
		return { type: 'SET_EDITOR', structure };
	},
	setEditorDirty: ( isEditorDirty ) => {
		return { type: 'SET_DIRTY', isEditorDirty };
	},
	setSaveCalled: ( isSaveCalled ) => {
		return { type: 'SET_SAVE_CALLED', isSaveCalled };
	},
};

/**
 * Course structure reducers.
 */
const reducers = {
	SET_SERVER: ( { structure }, state ) => {
		const hasStructureUpdate =
			state.structure && ! isEqual( structure, state.editor );
		return {
			...state,
			structure,
			editor: structure,
			isEditorDirty: false,
			hasStructureUpdate,
		};
	},
	SET_EDITOR: ( { structure }, state ) => {
		const isEditorDirty = ! isEqual( structure, state.structure );
		return {
			...state,
			editor: structure,
			isEditorDirty,
			hasStructureUpdate: state.hasStructureUpdate && isEditorDirty,
		};
	},
	SAVING: ( { isSaving }, state ) => ( {
		...state,
		isSaving,
	} ),
	SET_DIRTY: ( { isEditorDirty }, state ) => ( {
		...state,
		isEditorDirty,
	} ),
	SET_SAVE_CALLED: ( { isSaveCalled }, state ) => ( {
		...state,
		isSaveCalled,
	} ),
	DEFAULT: ( action, state ) => state,
};

/**
 * Course structure resolvers.
 */
const resolvers = {
	getStructure: () => actions.fetchCourseStructure(),
};

/**
 * Course structure  selectors
 */
const selectors = {
	getStructure: ( { structure } ) => structure,
	getEditorStructure: ( { editor } ) => editor,
	shouldSave: ( { isEditorDirty, isSaving } ) => ! isSaving && isEditorDirty,
	shouldResavePost: ( { isEditorDirty, isSaving, hasStructureUpdate } ) =>
		! isSaving && isEditorDirty && hasStructureUpdate,
	isSaveCalled: ( { isSaveCalled } ) => isSaveCalled,
};

export const COURSE_STORE = 'sensei/course-structure';

/**
 * Register course structure store and subscribe to block editor save.
 */
const registerCourseStructureStore = () => {
	/**
	 * Save course structure.
	 *
	 * @param {boolean} isSavingPost     Whether the post is saving.
	 * @param {boolean} shouldResavePost Whether the post should resave.
	 */
	const saveCourseStructure = ( isSavingPost, shouldResavePost ) => {
		const isSaveCalled = select( COURSE_STORE ).isSaveCalled();

		// Make sure to run the save once after every post saving.
		if ( isSavingPost ) {
			if ( isSaveCalled ) {
				return;
			}
			dispatch( COURSE_STORE ).setSaveCalled( true );
		} else {
			if ( isSaveCalled ) {
				dispatch( COURSE_STORE ).setSaveCalled( false );
			}
			return;
		}

		const shouldSave = select( COURSE_STORE ).shouldSave();

		// Clear error notices.
		dispatch( 'core/notices' ).removeNotice( 'course-outline-save-error' );

		if ( shouldSave && ! shouldResavePost ) {
			dispatch( COURSE_STORE ).save();
		}
	};

	subscribe( function saveStructureOnPostSave() {
		const editor = select( 'core/editor' );

		if ( ! editor ) return;

		const isSavingPost =
			editor.isSavingPost() && ! editor.isAutosavingPost();
		const shouldResavePost = select( COURSE_STORE ).shouldResavePost();

		// Save the post again if the blocks were updated.
		if ( ! isSavingPost && shouldResavePost ) {
			dispatch( 'core/editor' ).savePost();
		}

		saveCourseStructure( isSavingPost, shouldResavePost );
	} );

	registerStore( COURSE_STORE, {
		reducer: createReducerFromActionMap( reducers, DEFAULT_STATE ),
		actions,
		selectors,
		resolvers,
		controls: { ...dataControls },
	} );
};

registerCourseStructureStore();
