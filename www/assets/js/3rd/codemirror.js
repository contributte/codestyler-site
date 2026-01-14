import { EditorState } from 'https://esm.sh/@codemirror/state@6.5.0';
import { EditorView, keymap, lineNumbers, highlightActiveLine, highlightActiveLineGutter } from 'https://esm.sh/@codemirror/view@6.35.0?deps=@codemirror/state@6.5.0';
import { defaultKeymap, history, historyKeymap } from 'https://esm.sh/@codemirror/commands@6.7.1?deps=@codemirror/state@6.5.0';
import { php } from 'https://esm.sh/@codemirror/lang-php@6.0.1?deps=@codemirror/state@6.5.0';
import { oneDark } from 'https://esm.sh/@codemirror/theme-one-dark@6.1.2?deps=@codemirror/state@6.5.0';
import { syntaxHighlighting, defaultHighlightStyle, bracketMatching } from 'https://esm.sh/@codemirror/language@6.10.8?deps=@codemirror/state@6.5.0';

export function createEditor(options) {
	const extensions = [
		lineNumbers(),
		highlightActiveLine(),
		highlightActiveLineGutter(),
		history(),
		bracketMatching(),
		keymap.of([...defaultKeymap, ...historyKeymap]),
		php(),
		syntaxHighlighting(defaultHighlightStyle, { fallback: true }),
		oneDark,
		EditorView.theme({
			'&': {
				height: '100%',
			},
			'.cm-scroller': {
				overflow: 'auto',
			},
		}),
	];

	if (options.onChange) {
		extensions.push(
			EditorView.updateListener.of((update) => {
				if (update.docChanged) {
					options.onChange(update.state.doc.toString());
				}
			})
		);
	}

	if (options.readOnly) {
		extensions.push(EditorState.readOnly.of(true));
	}

	const state = EditorState.create({
		doc: options.doc ?? '',
		extensions,
	});

	return new EditorView({
		state,
		parent: options.parent,
	});
}

export function updateEditorContent(view, content) {
	view.dispatch({
		changes: {
			from: 0,
			to: view.state.doc.length,
			insert: content,
		},
	});
}
