import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import {
	SelectControl,
	TextControl,
	TextareaControl,
	ToggleControl,
} from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';

const DEFAULT_SCHEMA = 'none';

const LeanSeoPanel = () => {
	const postType = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostType(), [] );
	const [ meta = {}, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	const updateMeta = ( key, value ) => {
		setMeta( {
			...meta,
			[ key ]: value,
		} );
	};

	return (
		<PluginDocumentSettingPanel
			name="lean-seo-panel"
			title="Lean SEO"
			icon="admin-site-alt3"
			initialOpen={ true }
		>
			<TextControl
				label="SEO Titel"
				value={ meta._lean_seo_title || '' }
				onChange={ ( value ) => updateMeta( '_lean_seo_title', value ) }
			/>
			<TextareaControl
				label="Meta Description"
				value={ meta._lean_seo_desc || '' }
				onChange={ ( value ) => updateMeta( '_lean_seo_desc', value ) }
				rows={ 4 }
			/>
			<ToggleControl
				label="noindex"
				checked={ !! meta._lean_seo_noindex }
				onChange={ ( value ) => updateMeta( '_lean_seo_noindex', value ) }
			/>
			<ToggleControl
				label="nofollow"
				checked={ !! meta._lean_seo_nofollow }
				onChange={ ( value ) => updateMeta( '_lean_seo_nofollow', value ) }
			/>
			<SelectControl
				label="Schema.org Markup"
				value={ meta._lean_seo_schema || DEFAULT_SCHEMA }
				options={ [
					{ label: 'Kein Schema', value: 'none' },
					{ label: 'Artikel', value: 'article' },
					{ label: 'Local Business', value: 'local_business' },
					{ label: 'FAQ', value: 'faq' },
				] }
				onChange={ ( value ) => updateMeta( '_lean_seo_schema', value ) }
			/>
		</PluginDocumentSettingPanel>
	);
};

registerPlugin( 'lean-seo-sidebar', {
	render: LeanSeoPanel,
} );
