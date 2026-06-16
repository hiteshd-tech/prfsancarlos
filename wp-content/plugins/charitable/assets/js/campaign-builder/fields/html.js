/* global charitable_builder, wpchar, jconfirm, charitable_panel_switch, Choices, Charitable, CharitableCampaignEmbedWizard, wpCookies, tinyMCE, CharitableUtils, List */

var CharitableCampaignBuilderFieldHTML = window.CharitableCampaignBuilderFieldHTML || (function (document, window, $) {

    var $builder,
        cm_editor = false,
        cm_field_ids = [];

    var app = {

        settings: {
            load_html_option: false,
        },

        state: {
            editors: {},
            addEditor: function (id, editor) {
                this.editors[id] = editor;
            },
            getEditor: function (id) {
                return this.editors[id] || null;
            },
            removeEditor: function (id) {
                if (this.editors[id]) {
                    delete this.editors[id];
                }
            }
        },

        /**
         * Start the engine.
         *
         * @since 1.0.0
         */
        init: function () {
            wpchar.debug('init', 'field-html-js');

            var that = this;
            charitable_panel_switch = true;
            s = this.settings;

            // Add new handlers
            this.handleResize();
            this.setupCleanup();

            // Document ready
            $(app.ready);

            // Page load
            $(window).on('load', function () {
                if (typeof $.ready.then === 'function') {
                    $.ready.then(app.load);
                } else {
                    app.load();
                }
            });

            // Initialize error handling
            this.initErrorHandling();
        },

        initErrorHandling: function () {
            window.onerror = (msg, url, lineNo, columnNo, error) => {
                if (msg.indexOf('CodeMirror') !== -1) {
                    console.error('CodeMirror Error:', {
                        message: msg,
                        url: url,
                        lineNo: lineNo,
                        columnNo: columnNo,
                        error: error
                    });
                    return true; // Prevents the error from bubbling up
                }
                return false; // Let other errors bubble up
            };
        },

        recoverEditor: function (edit_field_id) {
            const editor = this.state.getEditor(edit_field_id);
            if (!editor || !editor.codemirror) {
                return;
            }

            try {
                // Attempt to recover editor state
                editor.codemirror.refresh();

                // If editor is hidden, try to make it visible
                const wrapper = editor.codemirror.getWrapperElement();
                if (wrapper && wrapper.style.display === 'none') {
                    wrapper.style.display = '';
                    editor.codemirror.refresh();
                }
            } catch (error) {
                console.error('Editor recovery failed:', error);
                this.destroy(edit_field_id);
                this.codemirrorInit(edit_field_id);
            }
        },

        initAutoSave: function (edit_field_id) {
            const editor = this.state.getEditor(edit_field_id);
            if (!editor || !editor.codemirror) {
                return;
            }

            let saveTimeout;
            editor.codemirror.on('change', () => {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    const content = editor.codemirror.getValue();
                    this.saveEditorContent(edit_field_id, content);
                }, 1000);
            });
        },

        saveEditorContent: function (edit_field_id, content) {
            const field_object = $('#charitable-panel-field-settings-field_html_html_' + edit_field_id);
            if (field_object.length) {
                field_object.val(content).trigger('change');
                wpchar.debug('Content saved for editor: ' + edit_field_id, 'field-html-js');
            }
        },

        cleanupMemory: function () {
            // Clear any unused editor instances
            Object.keys(this.state.editors).forEach(id => {
                const field_object = $('#charitable-panel-field-settings-field_html_html_' + id);
                if (!field_object.length) {
                    this.destroy(id);
                }
            });

            // Clear any orphaned CodeMirror instances
            $('.CodeMirror').each((index, element) => {
                const $element = $(element);
                const $textarea = $element.next('textarea');
                if (!$textarea.length) {
                    $element.remove();
                }
            });
        },

        initPeriodicCleanup: function () {
            setInterval(() => {
                this.cleanupMemory();
            }, 300000); // Run every 5 minutes
        },

        // Updated ready function with all new functionality integrated
        ready: function () {
            wpchar.debug('ready', 'field-html-js');

            $builder = $('#charitable-builder');

            // Initialize periodic cleanup
            app.initPeriodicCleanup();

            $builder.on('charitableFieldAddHTML', function (event, field_id, field_type) {

                wpchar.debug('Field added: ' + field_id, 'field-html-js');
                wpchar.debug('Field type: ' + field_type, 'field-html-js');

                if (field_type !== 'html') {
                    wpchar.debug('Filed type is not HTML: ' + field_type, 'field-html-js');
                    return;
                }

                if (parseInt(field_id) > 0) {
                    wpchar.debug('Field ID is valid: ' + field_id, 'field-html-js');
                    // Check if editor already exists
                    const existingEditor = app.state.getEditor(field_id);
                    if (existingEditor) {
                        wpchar.debug('Recovering existing codemirror at ' + field_id, 'field-html-js');
                        // app.recoverEditor(field_id);
                    } else if (cm_field_ids.indexOf(field_id) === -1) {
                        wpchar.debug('Initializing new codemirror at ' + field_id, 'field-html-js');
                        app.codemirrorInit(field_id);
                        app.initAutoSave(field_id);
                    }
                }
            });

            $builder.on('charitableFieldEdit', function (event, type, section, edit_field_id, field_id, field_type) {
                if (field_type !== 'html') {
                    return;
                }

                if (edit_field_id == '' && field_id !== '') {
                    edit_field_id = field_id;
                }

                if (parseInt(edit_field_id) > 0) {
                    // Check if editor already exists
                    const existingEditor = app.state.getEditor(edit_field_id);
                    if (existingEditor) {
                        app.recoverEditor(edit_field_id);
                    } else if (cm_field_ids.indexOf(edit_field_id) === -1) {
                        wpchar.debug('Initializing new codemirror at ' + edit_field_id, 'field-html-js');
                        app.codemirrorInit(edit_field_id);
                        app.initAutoSave(edit_field_id);
                    }
                }
            });

            // Add handler for field deletion
            $builder.on('charitableFieldDelete', function (event, field_id) {
                if (cm_field_ids.includes(field_id)) {
                    app.destroy(field_id);
                }
            });

            // Add handler for panel switches
            $builder.on('charitablePanelSwitch', function () {
                setTimeout(() => {
                    Object.keys(app.state.editors).forEach(id => {
                        const editor = app.state.getEditor(id);
                        if (editor && editor.codemirror) {
                            editor.codemirror.refresh();
                        }
                    });
                }, 100);
            });

            // Add handler for window focus
            $(window).on('focus', () => {
                Object.keys(app.state.editors).forEach(id => {
                    const editor = app.state.getEditor(id);
                    if (editor && editor.codemirror) {
                        editor.codemirror.refresh();
                    }
                });
            });
        },

        load: function () {
            wpchar.debug('load', 'field-html-js');

            // Refresh any existing editors after page load
            setTimeout(() => {
                Object.keys(app.state.editors).forEach(id => {
                    const editor = app.state.getEditor(id);
                    if (editor && editor.codemirror) {
                        editor.codemirror.refresh();
                    }
                });
            }, 500);
        },

        // Add this to your app object
        destroy: function (edit_field_id) {
            if (cm_editor && cm_editor.codemirror) {
                cm_editor.codemirror.toTextArea();
                cm_editor = null;
            }

            // Remove from tracked IDs
            const index = cm_field_ids.indexOf(edit_field_id);
            if (index > -1) {
                cm_field_ids.splice(index, 1);
            }
        },

        validateField: function (edit_field_id) {
            const field_object = $('#charitable-panel-field-settings-field_html_html_' + edit_field_id);

            if (!field_object.length) {
                wpchar.debug('Field not found: ' + edit_field_id, 'field-html-js');
                return false;
            }

            if (!field_object.is('textarea')) {
                wpchar.debug('Field is not a textarea: ' + edit_field_id, 'field-html-js');
                return false;
            }

            if (field_object.parent().find('.CodeMirror').length > 0) {
                wpchar.debug('CodeMirror already initialized for: ' + edit_field_id, 'field-html-js');
                return false;
            }

            return true;
        },

        waitForElement: function (field_id, callback, maxAttempts = 10) {
            let attempts = 0;

            const checkElement = setInterval(function () {
                attempts++;
                const element = $('#charitable-panel-field-settings-field_html_html_' + field_id);

                if (element.length) {
                    clearInterval(checkElement);
                    callback(element);
                    wpchar.debug('Trying to initialize field: ' + field_id, 'field-html-js');
                } else if (attempts >= maxAttempts) {
                    clearInterval(checkElement);
                    wpchar.debug('Field not found after ' + maxAttempts + ' attempts', 'field-html-js');
                }
            }, 500);
        },

        codemirrorInit: function (edit_field_id) {
            this.waitForElement(edit_field_id, (element) => {
                if (!this.validateField(edit_field_id)) {
                    return;
                }

                const settings = this.getEditorSettings();
                element.css('visibility', 'hidden');

                try {
                    setTimeout(() => {
                        const editor = wp.codeEditor.initialize(element[0], settings);

                        if (editor && editor.codemirror) {
                            this.state.addEditor(edit_field_id, editor);

                            editor.codemirror.on('change', () => {
                                editor.codemirror.save();
                                element.trigger('change');
                            });

                            element.css('visibility', 'visible');
                            cm_field_ids.push(edit_field_id);

                            editor.codemirror.setOption('extraKeys', {
                                'Ctrl-Space': 'autocomplete',
                                'Tab': function (cm) {
                                    if (cm.somethingSelected()) {
                                        cm.indentSelection('add');
                                    } else {
                                        cm.replaceSelection(Array(cm.getOption('indentUnit') + 1).join(' '));
                                    }
                                }
                            });

                            // Refresh editor after initialization to prevent rendering issues
                            editor.codemirror.refresh();

                            wpchar.debug('CodeMirror initialized for: ' + edit_field_id, 'field-html-js');
                        }
                    }, 500);
                } catch (error) {
                    console.error('CodeMirror initialization error:', error);
                    element.css('visibility', 'visible');
                }
            });
        },

        getEditorSettings: function () {
            const defaultSettings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};

            return {
                codemirror: _.extend({}, defaultSettings.codemirror, {
                    mode: 'html',
                    lineNumbers: true,
                    lineWrapping: true,
                    indentUnit: 2,
                    tabSize: 2,
                    autoCloseTags: true,
                    autoCloseBrackets: true,
                    matchBrackets: true,
                    foldGutter: true,
                    gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
                    extraKeys: {
                        'Ctrl-Space': 'autocomplete'
                    },
                    hintOptions: {
                        completeSingle: false
                    },
                    theme: 'default',
                    scrollbarStyle: 'overlay'
                })
            };
        },

        handleResize: function () {
            $(window).on('resize', _.debounce(() => {
                Object.values(this.state.editors).forEach(editor => {
                    if (editor && editor.codemirror) {
                        editor.codemirror.refresh();
                    }
                });
            }, 250));
        },

        setupCleanup: function () {
            $(window).on('beforeunload', () => {
                Object.keys(this.state.editors).forEach(id => {
                    this.destroy(id);
                });
            });
        },

        // Add diagnostic function for troubleshooting.
        diagnostics: function () {
            return {
                activeEditors: Object.keys(this.state.editors).length,
                trackedFields: cm_field_ids.length,
                editorInstances: $('.CodeMirror').length,
                textareaFields: $('[id^=charitable-panel-field-settings-field_html_html_]').length,
                memoryUsage: window.performance && window.performance.memory ?
                    window.performance.memory.usedJSHeapSize / 1048576 + ' MB' :
                    'Not available'
            };
        }

    }

    // Provide access to public functions/properties.
    return app;

}(document, window, jQuery));

CharitableCampaignBuilderFieldHTML.init();

