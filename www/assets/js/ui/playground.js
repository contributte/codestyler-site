import Alpine from 'https://esm.sh/alpinejs@3.14.3';
import { createEditor, updateEditorContent } from '../3rd/codemirror.js';

export function initPlayground() {
	Alpine.data('playground', (data) => ({
		code: data.sharedState?.code ?? `<?php declare(strict_types = 1);

namespace App;

class Example
{
    public function hello(): string
    {
        return "Hello, World!";
    }
}
`,
		selectedSniffs: data.sharedState?.sniffs ?? [],
		phpVersion: data.sharedState?.phpVersion ?? '8.4',
		sniffSearch: '',
		expandedStandards: [],
		loading: false,
		results: null,
		fixedCode: null,
		rulesetXml: null,
		rulesetTimeout: null,

		sniffs: data.sniffs,
		standards: data.standards,
		phpVersions: data.phpVersions,

		// Presets and Examples
		presets: data.presets ?? [],
		examples: data.examples ?? [],
		sniffProperties: data.sharedState?.properties ?? {},

		checkUrl: data.checkUrl,
		fixUrl: data.fixUrl,
		rulesetUrl: data.rulesetUrl,
		shareUrl: data.shareUrl,
		loadPresetUrl: data.loadPresetUrl,
		loadExampleUrl: data.loadExampleUrl,

		editor: null,
		fixedEditor: null,

		init() {
			this.$nextTick(() => {
				// Initialize input editor
				const editorEl = document.getElementById('code-editor');
				if (editorEl) {
					this.editor = createEditor({
						parent: editorEl,
						doc: this.code,
						onChange: (doc) => {
							this.code = doc;
						},
					});
				}

				// Initialize output editor (read-only)
				const fixedEditorEl = document.getElementById('fixed-editor');
				if (fixedEditorEl) {
					this.fixedEditor = createEditor({
						parent: fixedEditorEl,
						doc: '',
						readOnly: true,
					});
				}

				// Auto-generate ruleset on initial load if sniffs are pre-selected
				if (this.selectedSniffs.length > 0) {
					this.generateRuleset();
				}
			});

			// Watch for sniff changes and regenerate ruleset with debounce
			this.$watch('selectedSniffs', () => {
				this.debouncedGenerateRuleset();
			});

			// Update output editor when fixedCode changes
			this.$watch('fixedCode', (value) => {
				if (this.fixedEditor && value) {
					updateEditorContent(this.fixedEditor, value);
				}
			});
		},

		getSniffsByStandard(standard) {
			return Object.values(this.sniffs).filter((s) => s.standard === standard);
		},

		getSelectedCountByStandard(standard) {
			const standardSniffs = this.getSniffsByStandard(standard);
			return standardSniffs.filter((s) => this.selectedSniffs.includes(s.code)).length;
		},

		filteredSniffsByStandard(standard) {
			const sniffs = this.getSniffsByStandard(standard);
			if (!this.sniffSearch) {
				return sniffs;
			}
			const search = this.sniffSearch.toLowerCase();
			return sniffs.filter((s) =>
				s.name.toLowerCase().includes(search) ||
				s.code.toLowerCase().includes(search)
			);
		},

		toggleStandard(standard) {
			const index = this.expandedStandards.indexOf(standard);
			if (index === -1) {
				this.expandedStandards.push(standard);
			} else {
				this.expandedStandards.splice(index, 1);
			}
		},

		async checkCode() {
			if (this.selectedSniffs.length === 0) {
				alert('Please select at least one sniff');
				return;
			}

			this.loading = true;
			this.results = null;
			this.fixedCode = null;

			try {
				const response = await fetch(this.fixUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
					},
					body: JSON.stringify({
						code: this.code,
						sniffs: this.selectedSniffs,
						phpVersion: this.phpVersion,
					}),
				});

				const result = await response.json();
				this.results = {
					totals: result.totals,
					messages: result.messages,
				};
				this.fixedCode = result.fixedCode;
			} catch (error) {
				this.results = { error: 'Failed to check code' };
			} finally {
				this.loading = false;
			}
		},

		debouncedGenerateRuleset() {
			clearTimeout(this.rulesetTimeout);
			if (this.selectedSniffs.length === 0) {
				this.rulesetXml = null;
				return;
			}
			this.rulesetTimeout = setTimeout(() => {
				this.generateRuleset();
			}, 300);
		},

		async generateRuleset() {
			if (this.selectedSniffs.length === 0) {
				this.rulesetXml = null;
				return;
			}

			try {
				const response = await fetch(this.rulesetUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
					},
					body: JSON.stringify({
						sniffs: this.selectedSniffs,
						properties: this.sniffProperties,
					}),
				});

				const result = await response.json();
				this.rulesetXml = result.ruleset;
			} catch (error) {
				// Silently fail for auto-generation
			}
		},

		async shareConfig() {
			try {
				const response = await fetch(this.shareUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
					},
					body: JSON.stringify({
						code: this.code,
						sniffs: this.selectedSniffs,
						phpVersion: this.phpVersion,
						properties: this.sniffProperties,
					}),
				});

				const result = await response.json();
				await navigator.clipboard.writeText(result.url);
				alert('Share URL copied to clipboard!');
			} catch (error) {
				alert('Failed to create share link');
			}
		},

		copyRuleset() {
			if (this.rulesetXml) {
				navigator.clipboard.writeText(this.rulesetXml);
				alert('Ruleset copied to clipboard!');
			}
		},

		downloadRuleset() {
			if (!this.rulesetXml) return;

			const blob = new Blob([this.rulesetXml], { type: 'application/xml' });
			const url = URL.createObjectURL(blob);
			const a = document.createElement('a');
			a.href = url;
			a.download = 'ruleset.xml';
			document.body.appendChild(a);
			a.click();
			document.body.removeChild(a);
			URL.revokeObjectURL(url);
		},

		goToLine(line) {
			if (this.editor) {
				const lineInfo = this.editor.state.doc.line(line);
				this.editor.dispatch({
					selection: { anchor: lineInfo.from },
					scrollIntoView: true,
				});
				this.editor.focus();
			}
		},

		/**
		 * Load a preset (replaces current sniff selection)
		 */
		async loadPreset(presetId) {
			try {
				const response = await fetch(this.loadPresetUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
					},
					body: JSON.stringify({ preset: presetId }),
				});

				const result = await response.json();

				if (result.error) {
					alert(result.error);
					return;
				}

				// Replace current selection with preset sniffs
				this.selectedSniffs = result.sniffs;
				this.sniffProperties = result.properties || {};

				// Clear previous results
				this.results = null;
				this.fixedCode = null;

				// Regenerate ruleset and run check
				this.debouncedGenerateRuleset();
				this.checkCode();
			} catch (error) {
				alert('Failed to load preset');
			}
		},

		/**
		 * Load an example
		 * @param {string} exampleId
		 * @param {boolean} loadSniffs - If true, also load the associated sniffs
		 */
		async loadExample(exampleId, loadSniffs = true) {
			try {
				const response = await fetch(this.loadExampleUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
					},
					body: JSON.stringify({
						example: exampleId,
						loadSniffs: loadSniffs,
					}),
				});

				const result = await response.json();

				if (result.error) {
					alert(result.error);
					return;
				}

				// Update code in editor
				this.code = result.code;
				if (this.editor) {
					updateEditorContent(this.editor, result.code);
				}

				// Optionally update sniffs
				if (loadSniffs && result.sniffs) {
					this.selectedSniffs = result.sniffs;
					this.sniffProperties = result.properties || {};
				}

				// Clear previous results
				this.results = null;
				this.fixedCode = null;

				// Regenerate ruleset and run check
				this.debouncedGenerateRuleset();
				if (this.selectedSniffs.length > 0) {
					this.checkCode();
				}
			} catch (error) {
				alert('Failed to load example');
			}
		},
	}));
}
